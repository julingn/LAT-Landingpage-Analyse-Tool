<?php
/**
 * dwd.php — DWD Solar Data Proxy
 *
 * Ruft für eine PLZ / Stadt die nächste DWD-Sonnenstrahlungs-Messstation ab,
 * lädt deren jährliche Messwerte aus dem DWD OpenData-Portal und berechnet
 * Jahres-Mittelwerte für Globalstrahlung und Sonnenstunden.
 *
 * Actions (GET, kein CSRF nötig — nur lesend):
 *   ?action=solar&location=PLZ_oder_Stadt
 *   → {
 *       location, geocoded, lat, lon,
 *       station: { id, name, distance_km },
 *       irradiance_kWhm2_year,   // kWh/m²/Jahr (Globalstrahlung)
 *       sunshine_hours_year,     // h/Jahr
 *       dataYear,                // Referenzjahr der Messung (null = Schätzung)
 *       estimated,               // true wenn kein DWD-Datenabruf möglich war
 *       source                   // "DWD OpenData"
 *     }
 *
 * Datenquelle: https://opendata.dwd.de/climate_environment/CDC/observations_germany/climate/daily/solar/
 * Geocoding:   Nominatim / OpenStreetMap (kein API-Key, User-Agent pflicht)
 * Caching:     Temp-File mit 24h TTL je Station
 */

set_time_limit(30);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── Auth ──────────────────────────────────────────────────────────────────
session_start();
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}
session_write_close();

$action   = trim($_GET['action']   ?? '');
$location = trim($_GET['location'] ?? '');

if ($action !== 'solar') {
    http_response_code(400);
    echo json_encode(['error' => 'Unbekannte Action. Bitte action=solar verwenden.']);
    exit;
}
if ($location === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter location fehlt.']);
    exit;
}

// ── DWD Aktive Solarstationen (Stand: 2026-07, bis_datum >= 2025-01-01) ───
// Quelle: ST_Tageswerte_Beschreibung_Stationen.txt
$DWD_STATIONS = [
    ['id' => '00183', 'name' => 'Arkona',                'lat' => 54.6791, 'lon' => 13.4344],
    ['id' => '00662', 'name' => 'Braunschweig',          'lat' => 52.2915, 'lon' => 10.4464],
    ['id' => '00691', 'name' => 'Bremen',                'lat' => 53.0451, 'lon' =>  8.7981],
    ['id' => '00853', 'name' => 'Chemnitz',              'lat' => 50.7913, 'lon' => 12.8720],
    ['id' => '00867', 'name' => 'Lautertal-Oberlauter',  'lat' => 50.3066, 'lon' => 10.9679],
    ['id' => '01048', 'name' => 'Dresden-Klotzsche',     'lat' => 51.1278, 'lon' => 13.7543],
    ['id' => '01346', 'name' => 'Feldberg/Schwarzwald',  'lat' => 47.8748, 'lon' =>  8.0038],
    ['id' => '01358', 'name' => 'Fichtelberg',           'lat' => 50.4283, 'lon' => 12.9536],
    ['id' => '01420', 'name' => 'Frankfurt/Main',        'lat' => 50.0259, 'lon' =>  8.5213],
    ['id' => '01639', 'name' => 'Gießen',                'lat' => 50.6017, 'lon' =>  8.6439],
    ['id' => '01684', 'name' => 'Görlitz',               'lat' => 51.1621, 'lon' => 14.9506],
    ['id' => '01975', 'name' => 'Hamburg-Fuhlsbüttel',   'lat' => 53.6332, 'lon' =>  9.9881],
    ['id' => '02290', 'name' => 'Hohenpeißenberg',       'lat' => 47.8009, 'lon' => 11.0108],
    ['id' => '02483', 'name' => 'Kahler Asten',          'lat' => 51.1803, 'lon' =>  8.4891],
    ['id' => '02712', 'name' => 'Konstanz',              'lat' => 47.6952, 'lon' =>  9.1307],
    ['id' => '02925', 'name' => 'Leinefelde',            'lat' => 51.3932, 'lon' => 10.3123],
    ['id' => '02932', 'name' => 'Leipzig/Halle',         'lat' => 51.4347, 'lon' => 12.2396],
    ['id' => '03015', 'name' => 'Lindenberg',            'lat' => 52.2085, 'lon' => 14.1180],
    ['id' => '03631', 'name' => 'Norderney',             'lat' => 53.7123, 'lon' =>  7.1519],
    ['id' => '03668', 'name' => 'Nürnberg',              'lat' => 49.5030, 'lon' => 11.0549],
    ['id' => '03987', 'name' => 'Potsdam',               'lat' => 52.3812, 'lon' => 13.0622],
    ['id' => '04271', 'name' => 'Rostock-Warnemünde',    'lat' => 54.1803, 'lon' => 12.0808],
    ['id' => '04336', 'name' => 'Saarbrücken-Ensheim',   'lat' => 49.2128, 'lon' =>  7.1077],
    ['id' => '04393', 'name' => 'Sankt Peter-Ording',    'lat' => 54.3279, 'lon' =>  8.6031],
    ['id' => '04466', 'name' => 'Schleswig',             'lat' => 54.5275, 'lon' =>  9.5487],
    ['id' => '04642', 'name' => 'Seehausen',             'lat' => 52.8911, 'lon' => 11.7297],
    ['id' => '04928', 'name' => 'Stuttgart',             'lat' => 48.8281, 'lon' =>  9.2000],
    ['id' => '05100', 'name' => 'Trier-Petrisberg',      'lat' => 49.7479, 'lon' =>  6.6583],
    ['id' => '05142', 'name' => 'Ueckermünde',           'lat' => 53.7445, 'lon' => 14.0698],
    ['id' => '05404', 'name' => 'Weihenstephan-Dürnast', 'lat' => 48.4024, 'lon' => 11.6946],
    ['id' => '05705', 'name' => 'Würzburg',              'lat' => 49.7704, 'lon' =>  9.9576],
    ['id' => '05779', 'name' => 'Zinnwald-Georgenfeld',  'lat' => 50.7313, 'lon' => 13.7516],
    ['id' => '05792', 'name' => 'Zugspitze',             'lat' => 47.4210, 'lon' => 10.9848],
    ['id' => '05856', 'name' => 'Fürstenzell',           'lat' => 48.5451, 'lon' => 13.3532],
    ['id' => '05906', 'name' => 'Mannheim',              'lat' => 49.5063, 'lon' =>  8.5584],
    ['id' => '06197', 'name' => 'Lügde-Paenbruch',       'lat' => 51.8664, 'lon' =>  9.2710],
    ['id' => '07365', 'name' => 'Bochum',                'lat' => 51.4458, 'lon' =>  7.2628],
    ['id' => '07370', 'name' => 'Waldmünchen',           'lat' => 49.3910, 'lon' => 12.6838],
    ['id' => '15000', 'name' => 'Aachen-Orsbach',        'lat' => 50.7983, 'lon' =>  6.0244],
    ['id' => '15444', 'name' => 'Ulm-Mähringen',         'lat' => 48.4418, 'lon' =>  9.9216],
];

// ── 1. Geocoding: PLZ/Stadt → lat/lon (Nominatim) ────────────────────────
function dwdGeocodeLocation(string $location): ?array
{
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'q'           => $location,
        'countrycodes' => 'de',
        'format'      => 'json',
        'limit'       => 1,
        'addressdetails' => 0,
    ]);

    $ctx = stream_context_create(['http' => [
        'method'  => 'GET',
        'timeout' => 8,
        'header'  => implode("\r\n", [
            'User-Agent: LAT-Landingpage-Analyse-Tool/1.0 (PV Solar Generator; contact@lat-tool.de)',
            'Accept: application/json',
        ]),
    ]]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return null;

    $data = json_decode($raw, true);
    if (empty($data[0])) return null;

    return [
        'lat'         => (float) $data[0]['lat'],
        'lon'         => (float) $data[0]['lon'],
        'displayName' => $data[0]['display_name'] ?? $location,
    ];
}

// ── 2. Nächste DWD-Station (Haversine-Formel) ────────────────────────────
function dwdHaversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $R    = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a    = sin($dLat / 2) ** 2
          + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    return $R * 2 * asin(min(1.0, sqrt($a)));
}

function dwdFindNearestStation(float $lat, float $lon, array $stations): array
{
    $best    = null;
    $minDist = PHP_FLOAT_MAX;
    foreach ($stations as $st) {
        $d = dwdHaversineKm($lat, $lon, $st['lat'], $st['lon']);
        if ($d < $minDist) {
            $minDist = $d;
            $best    = array_merge($st, ['distance_km' => round($d, 1)]);
        }
    }
    return $best ?? $stations[0];
}

// ── 3. ZIP-Daten laden und Jahres-Mittelwert berechnen ───────────────────
function dwdFetchSolarData(string $stationId): ?array
{
    $id5     = str_pad($stationId, 5, '0', STR_PAD_LEFT);
    $baseUrl = 'https://opendata.dwd.de/climate_environment/CDC/observations_germany/climate/daily/solar/';

    // ─ Cache prüfen (24h TTL) ─────────────────────────────────────────
    $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "dwd_solar_{$id5}.json";
    if (is_file($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached) && !empty($cached['irradiance_kWhm2_year'])) {
            return $cached;
        }
    }

    // ─ ZIP herunterladen ──────────────────────────────────────────────
    $zipUrl = "{$baseUrl}tageswerte_ST_{$id5}_row.zip";
    $ctx    = stream_context_create(['http' => [
        'method'  => 'GET',
        'timeout' => 20,
        'header'  => 'User-Agent: LAT-Landingpage-Analyse-Tool/1.0',
    ]]);

    $zipData = @file_get_contents($zipUrl, false, $ctx);
    if ($zipData === false || strlen($zipData) < 500) return null;

    // ─ ZIP in temporäre Datei schreiben ──────────────────────────────
    $tmpZip = tempnam(sys_get_temp_dir(), 'dwd_') . '.zip';
    if (file_put_contents($tmpZip, $zipData) === false) return null;

    // ─ CSV-Inhalt extrahieren (ZipArchive, Fallback: PharData) ───────
    $csvContent = null;

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($tmpZip) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (strpos($name, 'produkt_st_tag') !== false && substr($name, -4) === '.txt') {
                    $csvContent = $zip->getFromIndex($i);
                    break;
                }
            }
            $zip->close();
        }
    }

    // Fallback: PharData (phar-Extension, in PHP-CLI standardmäßig aktiv)
    if ($csvContent === null && class_exists('PharData')) {
        try {
            $phar = new PharData($tmpZip);
            foreach ($phar as $fileInfo) {
                $name = basename((string) $fileInfo);
                if (strpos($name, 'produkt_st_tag') !== false && substr($name, -4) === '.txt') {
                    $csvContent = $fileInfo->getContent();
                    break;
                }
            }
        } catch (Throwable $e) {
            // PharData fehlgeschlagen — $csvContent bleibt null
        }
    }

    @unlink($tmpZip);
    if ($csvContent === null) return null;

    // ─ CSV parsen ─────────────────────────────────────────────────────
    $lines  = explode("\n", str_replace("\r", "", trim($csvContent)));
    $header = array_map('trim', explode(';', array_shift($lines)));

    $fgIdx  = array_search('FG_LBERG',   $header); // Globalstrahlung J/cm²
    $sdIdx  = array_search('SD_SO',      $header); // Sonnenscheindauer h
    $dtIdx  = array_search('MESS_DATUM', $header); // Datum YYYYMMDD

    if ($fgIdx === false || $sdIdx === false || $dtIdx === false) return null;

    // Letztes komplettes Kalenderjahr (z.B. 2025 wenn aktuelles Jahr 2026)
    $targetYear = (int) date('Y') - 1;
    $yearStart  = $targetYear * 10000 + 101;   // YYYYMMDD als int
    $yearEnd    = $targetYear * 10000 + 1231;

    $sumFG = 0.0;
    $sumSD = 0.0;
    $count = 0;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line === 'eor') continue;

        $cols = explode(';', $line);
        $maxIdx = max($fgIdx, $sdIdx, $dtIdx);
        if (count($cols) <= $maxIdx) continue;

        $dt = (int) trim($cols[$dtIdx]);
        if ($dt < $yearStart || $dt > $yearEnd) continue;

        $fg = trim($cols[$fgIdx]);
        $sd = trim($cols[$sdIdx]);

        if ($fg !== '-999' && is_numeric($fg) && (float)$fg >= 0) {
            $sumFG += (float) $fg;
        }
        if ($sd !== '-999' && is_numeric($sd) && (float)$sd >= 0) {
            $sumSD += (float) $sd;
        }
        $count++;
    }

    // Mindestens 300 Messtage erforderlich (aus 365)
    if ($count < 300) return null;

    // Einheitenumrechnung:
    // FG_LBERG: J/cm² → kWh/m²
    //   1 J/cm² = 10.000 J/m² = 10.000/3.600.000 kWh/m² = 1/360 kWh/m²
    $irradiance = (int) round($sumFG / 360.0);
    $sunshine   = (int) round($sumSD);

    $result = [
        'irradiance_kWhm2_year' => $irradiance,
        'sunshine_hours_year'   => $sunshine,
        'dataYear'              => $targetYear,
        'dataPoints'            => $count,
        'estimated'             => false,
    ];

    // Cache schreiben
    @file_put_contents($cacheFile, json_encode($result));

    return $result;
}

// ── 5. Deutschland-Durchschnitt (DWD Regionalmittel) ─────────────────────
// Quelle: regional_averages_sd_year.txt — Zeitreihe 1951-aktuell
// Spaltenreihenfolge: Jahr;year;BB/B;BB;BW;BY;HE;MV;NI;NI/HH/HB;NW;RP;SH;SL;SN;ST;TH/ST;TH;Deutschland
function dwdFetchGermanyAvg(): ?array
{
    $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dwd_germany_avg.json';
    if (is_file($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {
        $c = json_decode(file_get_contents($cacheFile), true);
        if (is_array($c) && !empty($c['sunshine_hours_year'])) return $c;
    }

    $url = 'https://opendata.dwd.de/climate_environment/CDC/regional_averages_DE/annual/sunshine_duration/regional_averages_sd_year.txt';
    $ctx = stream_context_create(['http' => [
        'method'  => 'GET',
        'timeout' => 10,
        'header'  => 'User-Agent: LAT-Landingpage-Analyse-Tool/1.0',
    ]]);
    $raw = @file_get_contents($url, false, $ctx);
    if (!$raw || strlen($raw) < 500) return null;

    // Zeilen zusammenfügen: jede Jahresdatenreihe über mehrere Zeilen → zusammenkleben
    $lines   = array_map('trim', explode("\n", str_replace("\r", '', $raw)));
    $current = '';
    $yearValues = [];

    $processBlock = function(string $block) use (&$yearValues): void {
        $parts = array_map('trim', explode(';', $block));
        // Index 0 = Jahr, 1 = "year", 2-17 = Bundesländer, 18 = Deutschland
        if (count($parts) >= 19 && is_numeric($parts[0]) && is_numeric($parts[18])) {
            $yearValues[(int)$parts[0]] = (float)$parts[18];
        }
    };

    foreach ($lines as $line) {
        if ($line === '' || preg_match('/^[A-Za-zÄÖÜäöüß]/', $line)) continue; // Kopfzeilen
        if (preg_match('/^\d{4};year/', $line)) {
            if ($current !== '') $processBlock($current);
            $current = $line;
        } else {
            $current .= $line; // Fortsetzungszeile
        }
    }
    if ($current !== '') $processBlock($current);
    if (empty($yearValues)) return null;

    // Letztes verfügbares Jahr (bis max. aktuelles Jahr)
    $lastYear = max(array_filter(array_keys($yearValues), fn($y) => $y <= (int)date('Y')));

    // Klimanormal 1991–2020
    $normalVals = [];
    for ($y = 1991; $y <= 2020; $y++) {
        if (isset($yearValues[$y])) $normalVals[] = $yearValues[$y];
    }
    $klimanormal = count($normalVals) >= 25
        ? (int) round(array_sum($normalVals) / count($normalVals))
        : null;

    $result = [
        'sunshine_hours_year'   => (int) round($yearValues[$lastYear]),
        'year'                  => $lastYear,
        'klimanormal_1991_2020' => $klimanormal,
        'irradiance_kWhm2_year' => 1073, // DWD Klimanormal 1991–2020 (Globalstrahlung, fest)
        'source'                => 'DWD Regionalmittel',
    ];
    @file_put_contents($cacheFile, json_encode($result));
    return $result;
}

// ── 4. Geographische Schätzung als Fallback ───────────────────────────────
// Basiert auf bekannten DWD-Klimanormalen für die Breitengradzone.
function dwdEstimateSolarByLat(float $lat): array
{
    // Grobe Klimazonen Deutschland (Nord → Süd steigt Strahlung)
    if ($lat >= 53.5)      $irr = 980;   // Nordsee/Ostsee
    elseif ($lat >= 52.5)  $irr = 1010;  // Norddeutschland
    elseif ($lat >= 51.5)  $irr = 1040;  // Mitteldeutschland-Nord
    elseif ($lat >= 50.5)  $irr = 1070;  // Mitteldeutschland-Süd
    elseif ($lat >= 49.5)  $irr = 1100;  // Franken/Hessen
    elseif ($lat >= 48.5)  $irr = 1130;  // Bayern/BW-Nord
    else                   $irr = 1160;  // Bayern-Süd/Oberrhein

    return [
        'irradiance_kWhm2_year' => $irr,
        'sunshine_hours_year'   => (int) round($irr * 1.72), // DWD-Richtwert: ~1.72 h/(kWh/m²)
        'dataYear'              => null,
        'dataPoints'            => 0,
        'estimated'             => true,
    ];
}

// ── Main ─────────────────────────────────────────────────────────────────
$geo = dwdGeocodeLocation($location);
if ($geo === null) {
    http_response_code(404);
    echo json_encode(['error' => "Ort oder PLZ '{$location}' konnte nicht geocodiert werden."]);
    exit;
}

$station = dwdFindNearestStation($geo['lat'], $geo['lon'], $DWD_STATIONS);

$solarData = dwdFetchSolarData($station['id']);
if ($solarData === null) {
    $solarData = dwdEstimateSolarByLat($geo['lat']);
}

$germanyAvg = dwdFetchGermanyAvg(); // Kann null sein wenn DWD nicht erreichbar

echo json_encode([
    'location'              => $location,
    'geocoded'              => $geo['displayName'],
    'lat'                   => round($geo['lat'], 4),
    'lon'                   => round($geo['lon'], 4),
    'station'               => [
        'id'          => $station['id'],
        'name'        => $station['name'],
        'distance_km' => $station['distance_km'],
    ],
    'irradiance_kWhm2_year' => $solarData['irradiance_kWhm2_year'],
    'sunshine_hours_year'   => $solarData['sunshine_hours_year'],
    'dataYear'              => $solarData['dataYear'],
    'dataPoints'            => $solarData['dataPoints'],
    'estimated'             => $solarData['estimated'],
    'germany_avg'           => $germanyAvg, // null wenn nicht verfügbar
    'source'                => 'DWD OpenData',
], JSON_UNESCAPED_UNICODE);
