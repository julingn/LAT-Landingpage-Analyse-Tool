<?php
/**
 * ux.php — UX/CRO Analyse Proxy (M5 v2)
 *
 * Deterministisch: Score aus HTML-Parsing + PSI-Daten
 * LLM: nur Kommentartext (beeinflusst Score nicht)
 * Device-Split: Desktop (1280px) + Mobile (375px)
 *
 * Actions:
 *   analyze  POST -> { url, html, device:'mobile'|'desktop', psi_data:{...}, csrf_token }
 *                 -> { success, device, score, checks:[{id,name,status,finding,detail,fix,comment}],
 *                     screenshot_base64 }
 */

session_start();
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}
session_write_close();

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? '';

function takeScreenshot(string $url, int $width, int $height): ?string {
    $tmpFile = '/tmp/ux_shot_' . bin2hex(random_bytes(8)) . '.png';
    $scriptPath = dirname(__DIR__, 2) . '/screenshot.mjs';
    $nodeCmd    = trim((string)shell_exec('which node 2>/dev/null')) ?: '/usr/bin/node';
    if (file_exists($scriptPath) && file_exists($nodeCmd)) {
        $cmd = escapeshellarg($nodeCmd)
            . ' ' . escapeshellarg($scriptPath)
            . ' ' . escapeshellarg($url)
            . ' ' . escapeshellarg($tmpFile)
            . ' ' . (int)$width . ' ' . (int)$height;
        $proc = proc_open($cmd, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, null, [
            'PUPPETEER_SKIP_CHROMIUM_DOWNLOAD' => 'true',
            'PUPPETEER_EXECUTABLE_PATH'        => getenv('CHROMIUM_PATH') ?: '/usr/bin/chromium',
            'CHROMIUM_PATH'                    => getenv('CHROMIUM_PATH') ?: '/usr/bin/chromium',
        ]);
        if (is_resource($proc)) {
            fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
            $exitCode = proc_close($proc);
            if ($exitCode === 0 && file_exists($tmpFile)) {
                $b64 = base64_encode(file_get_contents($tmpFile));
                unlink($tmpFile);
                return $b64;
            }
        }
    }
    $candidates = [getenv('CHROMIUM_PATH')?:'','/usr/bin/chromium','/usr/bin/chromium-browser','/usr/bin/google-chrome','/usr/bin/google-chrome-stable'];
    $chromium = '';
    foreach ($candidates as $c) { if ($c && file_exists($c) && is_executable($c)) { $chromium = $c; break; } }
    if ($chromium) {
        $cmd2 = $chromium
            . ' --headless=new --no-sandbox --disable-dev-shm-usage --disable-gpu'
            . ' --disable-extensions --disable-software-rasterizer'
            . ' --virtual-time-budget=6000'
            . ' --window-size=' . (int)$width . ',' . (int)$height
            . ' --screenshot=' . escapeshellarg($tmpFile)
            . ' ' . escapeshellarg($url) . ' 2>/dev/null';
        exec($cmd2, $out, $code);
        if ($code === 0 && file_exists($tmpFile)) {
            $b64 = base64_encode(file_get_contents($tmpFile));
            unlink($tmpFile);
            return $b64;
        }
    }
    return null;
}

function runUxChecks(string $html, string $url, string $device, array $psi): array {
    $doc = null;
    if ($html) {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
    }
    $checks = [];

    // U1: Above-the-Fold & Nutzenversprechen
    $h1Count = $doc ? $doc->getElementsByTagName('h1')->length : 0;
    $h1Text  = '';
    if ($doc && $h1Count > 0) $h1Text = trim($doc->getElementsByTagName('h1')->item(0)->textContent ?? '');
    $bodyStart = '';
    if ($doc) {
        $bodies = $doc->getElementsByTagName('body');
        if ($bodies->length > 0) $bodyStart = substr(trim(preg_replace('/\s+/',' ',$bodies->item(0)->textContent??'')),0,2000);
    }
    $wordCount = str_word_count($bodyStart);
    $u1Status  = $h1Count === 1 && $wordCount >= 20 ? 'green' : ($h1Count > 0 ? 'amber' : 'red');
    $checks[]  = [
        'id'=>'U1','name'=>'Above-the-Fold & Nutzenversprechen','status'=>$u1Status,
        'finding'=>$h1Count===0?'Kein H1-Tag — Nutzenversprechen nicht erkennbar.':($h1Count>1?$h1Count.' H1-Tags — Hauptversprechen unklar.':'H1 vorhanden'.($h1Text?': "'.mb_substr($h1Text,0,60).(mb_strlen($h1Text)>60?'…':'').'"':'').($wordCount>=20?' · Inhalt above the fold erkannt.'>' · Wenig sichtbarer Text.')),
        'detail'=>$u1Status!=='green'?'Besucher entscheiden in 3 Sek. ob sie bleiben. Headline + Subline + visuelles Element müssen sofort sichtbar sein.':'',
        'fix'=>$h1Count===0?'H1-Headline mit klarem Nutzenversprechen ergänzen.':($wordCount<20?'Mehr Inhalt above the fold bringen — Hero-Text und Subline ergänzen.':''),
    ];

    // U2: Ablenkungsfreiheit & Benutzerführung
    $navLinkCount = 0; $hasMainNav = false;
    if ($doc) {
        foreach ($doc->getElementsByTagName('nav') as $nav) {
            $l = $nav->getElementsByTagName('a')->length;
            $navLinkCount += $l;
            if ($l >= 3) $hasMainNav = true;
        }
        foreach ($doc->getElementsByTagName('header') as $h) {
            $l = $h->getElementsByTagName('a')->length;
            if ($l >= 4) { $hasMainNav = true; $navLinkCount = max($navLinkCount, $l); }
        }
    }
    $u2Status = !$hasMainNav?'green':($navLinkCount<=5?'amber':'red');
    $checks[] = [
        'id'=>'U2','name'=>'Ablenkungsfreiheit & Benutzerführung','status'=>$u2Status,
        'finding'=>!$hasMainNav?'Keine ablenkende Hauptnavigation erkannt.':'Hauptnavigation mit '.$navLinkCount.' Links'.($device==='mobile'?' — auf Mobile ablenkend.':'.'),
        'detail'=>$hasMainNav&&$navLinkCount>5?'Jeder Nav-Link ist eine Ausstiegsoption. Landingpages entfernen die Navigation idealerweise komplett.':'',
        'fix'=>$hasMainNav?($device==='mobile'?'Navigation auf Mobile ausblenden oder auf Logo + CTA reduzieren.':'Navigation auf Landingpages ausblenden — nur Logo + CTA-Button behalten.'):'',
    ];

    // U3: Call-to-Action
    $ctaKeywords = ['jetzt','kaufen','bestellen','wechseln','starten','sichern','anmelden','registrieren','buchen','anfragen','downloaden','herunterladen','testen','vergleichen','berechnen','erhalten','now','buy','start','get'];
    $ctaCount = 0; $ctaExamples = [];
    if ($doc) {
        foreach ($doc->getElementsByTagName('button') as $btn) {
            $txt = strtolower(trim($btn->textContent??''));
            foreach ($ctaKeywords as $kw) {
                if (str_contains($txt,$kw)) { $ctaCount++; if(count($ctaExamples)<2)$ctaExamples[]=trim($btn->textContent??''); break; }
            }
        }
        foreach ($doc->getElementsByTagName('a') as $a) {
            $txt=strtolower(trim($a->textContent??'')); $cls=strtolower($a->getAttribute('class')??'');
            $isCta=false;
            foreach ($ctaKeywords as $kw) { if(str_contains($txt,$kw)){$isCta=true;break;} }
            if(!$isCta&&(str_contains($cls,'btn')||str_contains($cls,'button')||str_contains($cls,'cta')))$isCta=true;
            if($isCta){$ctaCount++;if(count($ctaExamples)<2)$ctaExamples[]=trim($a->textContent??'');}
        }
    }
    $u3Status=$ctaCount===0?'red':($ctaCount===1?'amber':'green');
    $u3Finding = $ctaCount===0 ? 'Kein CTA-Button gefunden.'
        : ($ctaCount===1 ? '1 CTA: '.(count($ctaExamples)?'"'.mb_substr($ctaExamples[0],0,40).'"':'').' — nur ein CTA kann zu wenig sein.'
        : $ctaCount.' CTAs gefunden'.(count($ctaExamples)?': "'.implode('", "',array_map(fn($e)=>mb_substr($e,0,30),$ctaExamples)).'"':'').'.');
    $checks[]=['id'=>'U3','name'=>'Call-to-Action','status'=>$u3Status,
        'finding'=>$u3Finding,
        'detail'=>$ctaCount===0?'Ohne CTA weiß der Besucher nicht was er tun soll.':($ctaCount===1?'Mindestens 1 CTA above the fold + 1 weiter unten empfohlen.':''),
        'fix'=>$ctaCount===0?'Primären CTA mit handlungsorientiertem Text ergänzen (z.B. "Jetzt berechnen" statt "Absenden").':($ctaCount===1?'Zweiten CTA weiter unten ergänzen.':''),
    ];

    // U4: Trust & Social Proof
    $trustSignals=0; $trustFound=[];
    if ($doc) {
        $fullText=strtolower($doc->textContent??'');
        foreach ($doc->getElementsByTagName('script') as $s) {
            if(str_contains($s->getAttribute('type')??'','application/ld+json')){
                $ld=json_decode($s->textContent??'',true);
                if(is_array($ld)&&(isset($ld['aggregateRating'])||str_contains(strtolower($ld['@type']??''),'product'))){$trustSignals++;$trustFound[]='Schema.org AggregateRating';}
            }
        }
        $tkws=['bewertung','kundenmeinung','testimonial','zertifikat','zertifiziert','auszeichnung','siegel','tüv','trusted','geprüft','empfehlung'];
        $found=[];foreach($tkws as $kw){if(str_contains($fullText,$kw))$found[]=$kw;}
        if(count($found)>=2){$trustSignals++;$trustFound[]='Trust-Keywords ('.implode(', ',array_slice($found,0,3)).')';}
        foreach($doc->getElementsByTagName('img') as $img){
            $alt=strtolower($img->getAttribute('alt')??'');$src=strtolower($img->getAttribute('src')??'');
            foreach(['siegel','logo','award','zertifikat','trusted','partner','tüv','bewertung'] as $kw){
                if(str_contains($alt,$kw)||str_contains($src,$kw)){$trustSignals++;$trustFound[]='Trust-Bild: '.$img->getAttribute('alt');break;}
            }
            if(count($trustFound)>=4)break;
        }
    }
    $u4Status=$trustSignals>=2?'green':($trustSignals===1?'amber':'red');
    $checks[]=['id'=>'U4','name'=>'Trust & Social Proof','status'=>$u4Status,
        'finding'=>$trustSignals===0?'Keine Trust-Signale erkannt.':($trustSignals===1?'1 Trust-Signal: '.($trustFound[0]??''):count($trustFound).' Trust-Signale: '.implode(' · ',array_slice($trustFound,0,3))),
        'detail'=>$trustSignals<2?'Trust-Elemente reduzieren das wahrgenommene Kaufrisiko erheblich.':'',
        'fix'=>$trustSignals===0?'Kundenbewertungen, Gütesiegel oder Partnerlogos ergänzen.':($trustSignals===1?'Trust-Signale ausbauen — Bewertungen + Zertifikat kombinieren.':''),
    ];

    // U5: Performance
    if($psi&&isset($psi['perf_score'])){
        $s=(int)($psi['perf_score']??0);$lcp=(float)($psi['lcp']??0);$cls=(float)($psi['cls']??0);$tbt=(float)($psi['tbt']??0);
        $issues=[];
        if($lcp>2.5)$issues[]='LCP '.$lcp.'s (Ziel: <2,5s)';
        if($cls>0.1)$issues[]='CLS '.$cls.' (Ziel: <0,1)';
        if($tbt>200)$issues[]='TBT '.$tbt.'ms (Ziel: <200ms)';
        $u5Status=$s>=90?'green':($s>=50?'amber':'red');
        $checks[]=['id'=>'U5','name'=>'Performance '.strtoupper($device),'status'=>$u5Status,
            'finding'=>'PageSpeed-Score: '.$s.'/100 · LCP: '.$lcp.'s · CLS: '.$cls.' · TBT: '.$tbt.'ms',
            'detail'=>count($issues)?implode(' · ',$issues):'',
            'fix'=>$s<90?'Bilder in WebP konvertieren, Render-blocking Ressourcen entfernen. Details: https://pagespeed.web.dev/':'',
        ];
    } else {
        $checks[]=['id'=>'U5','name'=>'Performance '.strtoupper($device),'status'=>'amber',
            'finding'=>'Keine PageSpeed-Daten verfügbar.','detail'=>'','fix'=>''];
    }
    return $checks;
}

function getLlmComment(string $b64, string $url, string $device, array $checks): string {
    $anthropicKey=CFG_ANTHROPIC_KEY; $openaiKey=CFG_OPENAI_KEY; $provider=CFG_AI_PROVIDER;
    if(!$anthropicKey&&!$openaiKey)return '';
    $ctx="Gerät: ".strtoupper($device)."\nDeterministische Checks:\n";
    foreach($checks as $c)$ctx.="- [{$c['id']} {$c['status']}] {$c['name']}: {$c['finding']}\n";
    $sys='Du bist ein UX/CRO-Experte. Analysiere den '.strtoupper($device).'-Screenshot einer Landingpage als Ergänzung zu den bereitgestellten deterministischen Checks. Schreibe 3–4 Sätze auf Deutsch: Was fällt visuell besonders auf? Was nimmt ein Nutzer als erstes wahr? Beziehe dich auf die Checks ohne sie wörtlich zu wiederholen. Kein JSON, kein Markdown — nur Fließtext.';
    $userMsg=$ctx."\nBitte qualitative Einschätzung.";
    $text='';
    if($provider==='anthropic'&&$anthropicKey){
        $payload=['model'=>CFG_AI_MODEL?:'claude-sonnet-4-5','max_tokens'=>600,'system'=>$sys,'messages'=>[['role'=>'user','content'=>[
            ['type'=>'image','source'=>['type'=>'base64','media_type'=>'image/png','data'=>$b64]],
            ['type'=>'text','text'=>$userMsg],
        ]]]];
        $ch=curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_TIMEOUT=>60,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_HTTPHEADER=>['x-api-key: '.$anthropicKey,'anthropic-version: 2023-06-01','content-type: application/json']]);
        $resp=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        if($code===200){$d=json_decode($resp,true);$text=trim($d['content'][0]['text']??'');}
    }elseif($openaiKey){
        $payload=['model'=>CFG_OPENAI_MODEL?:'gpt-4o','max_tokens'=>600,'messages'=>[
            ['role'=>'system','content'=>$sys],
            ['role'=>'user','content'=>[['type'=>'image_url','image_url'=>['url'=>'data:image/png;base64,'.$b64,'detail'=>'high']],['type'=>'text','text'=>$userMsg]]],
        ]];
        $ch=curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_TIMEOUT=>60,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$openaiKey,'Content-Type: application/json']]);
        $resp=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        if($code===200){$d=json_decode($resp,true);$text=trim($d['choices'][0]['message']['content']??'');}
    }
    return $text;
}

if($action==='analyze'){
    session_start();
    $body=json_decode(file_get_contents('php://input'),true)??[];
    if(empty($body['csrf_token'])||$body['csrf_token']!==($_SESSION['csrf_token']??'')){
        session_write_close();http_response_code(403);echo json_encode(['success'=>false,'error'=>'CSRF-Fehler']);exit;
    }
    session_write_close();
    $url=trim($body['url']??'');$html=$body['html']??'';
    $device=in_array($body['device']??'',['mobile','desktop'])?$body['device']:'mobile';
    $psi=is_array($body['psi_data']??null)?$body['psi_data']:[];
    if(!$url||!filter_var($url,FILTER_VALIDATE_URL)){echo json_encode(['success'=>false,'error'=>'Ungültige URL']);exit;}
    $scheme=strtolower(parse_url($url,PHP_URL_SCHEME)??'');
    if(!in_array($scheme,['http','https'],true)){echo json_encode(['success'=>false,'error'=>'Nur HTTP/HTTPS erlaubt']);exit;}
    $width=$device==='mobile'?375:1280;$height=$device==='mobile'?812:900;
    $screenshotBase64=takeScreenshot($url,$width,$height);
    if(!$screenshotBase64){echo json_encode(['success'=>false,'error'=>'Screenshot fehlgeschlagen']);exit;}
    $checks=runUxChecks($html,$url,$device,$psi);
    $scoreMap=['green'=>100,'amber'=>50,'red'=>0];
    $total=count($checks);$sum=array_sum(array_map(fn($c)=>$scoreMap[$c['status']]??0,$checks));
    $score=$total>0?(int)round($sum/$total):0;
    $comment=getLlmComment($screenshotBase64,$url,$device,$checks);
    echo json_encode(['success'=>true,'device'=>$device,'score'=>$score,'comment'=>$comment,'checks'=>$checks,'screenshot_base64'=>$screenshotBase64]);
    exit;
}

http_response_code(400);
echo json_encode(['error'=>'Unbekannte Aktion: '.htmlspecialchars($action)]);
