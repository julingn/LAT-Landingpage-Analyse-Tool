/**
 * screenshot.mjs — Headless Chromium Screenshot mit Cookie-Banner-Dismissal
 *
 * Aufruf: node screenshot.mjs <url> <output.png>
 *
 * Cookie-Banner wird automatisch geschlossen bevor der Screenshot gemacht wird.
 * Unterstützt: Cookiebot, OneTrust, Usercentrics, Consentmanager, Borlabs,
 *              und generische "Alle akzeptieren"-Buttons.
 *
 * Exit 0 = Erfolg (PNG wurde geschrieben)
 * Exit 1 = Fehler (Fehlermeldung auf stderr)
 */

import puppeteer from 'puppeteer-core';
import { existsSync } from 'fs';
import { writeFileSync } from 'fs';

const [,, url, outFile, widthArg, heightArg] = process.argv;
const vpWidth  = parseInt(widthArg,  10) || 1280;
const vpHeight = parseInt(heightArg, 10) || 900;

if (!url || !outFile) {
  process.stderr.write('Usage: node screenshot.mjs <url> <output.png>\n');
  process.exit(1);
}

// Chromium-Binary ermitteln
const candidates = [
  process.env.PUPPETEER_EXECUTABLE_PATH,
  process.env.CHROMIUM_PATH,
  '/usr/bin/chromium',
  '/usr/bin/chromium-browser',
  '/usr/bin/google-chrome',
  '/usr/bin/google-chrome-stable',
];
const execPath = candidates.find(p => p && existsSync(p));
if (!execPath) {
  process.stderr.write('Chromium nicht gefunden\n');
  process.exit(1);
}

// Cookie-Banner-Selektoren (Priorität: spezifisch → generisch)
// Reihenfolge: erst spezifische CMP-IDs, dann generische Text-Matches
const ACCEPT_SELECTORS = [
  // Cookiebot
  '#CybotCookiebotDialogBodyLevelButtonLevelOptinAllowAll',
  '#CybotCookiebotDialogBodyButtonAccept',
  // OneTrust
  '#onetrust-accept-btn-handler',
  '.onetrust-accept-btn-handler',
  '#accept-recommended-btn-handler',
  // Usercentrics
  '[data-testid="uc-accept-all-button"]',
  'button[data-testid="accept-all"]',
  // Consentmanager
  '#cmBtn_1',
  '.cmpboxbtnyes',
  // Borlabs Cookie
  '#borlabs-cookie-btn-accept-all',
  '.borlabs-cookie-btn.accept',
  // Klaro
  '.klaro .cm-btn.cm-btn-success',
  // Cookie Consent (Insites)
  '.cc-btn.cc-allow',
  '.cc-accept',
  // Quantcast
  '.qc-cmp2-summary-buttons button:first-child',
  // iubenda
  '#iubenda-cs-accept-btn',
  // Didomi
  '#didomi-notice-agree-button',
  // TrustArc
  '.trustarc-agree-btn',
  '.truste_overlay .pdynamicbutton .call',
  // Generic — breite Matches nach Text-Inhalt (werden per evaluate() geprüft)
];

// Generische Text-Patterns für querySelector + innerText check
const ACCEPT_TEXT_PATTERNS = [
  /alle\s+(akzeptieren|annehmen|zulassen|erlauben)/i,
  /accept\s+all/i,
  /akzeptiere\s+alle/i,
  /alles\s+akzeptieren/i,
  /alle\s+cookies\s+akzeptieren/i,
  /accept\s+cookies/i,
  /agree\s+to\s+all/i,
  /zustimmen/i,
  /einverstanden/i,
  /ich\s+stimme\s+zu/i,
  /allow\s+all/i,
  /ok,\s+akzeptieren/i,
];

async function dismissCookieBanner(page) {
  // 1. Spezifische CSS-Selektoren versuchen
  for (const sel of ACCEPT_SELECTORS) {
    try {
      const el = await page.$(sel);
      if (el) {
        const visible = await el.isIntersectingViewport().catch(() => true);
        if (visible) {
          await el.click();
          return true;
        }
      }
    } catch (_) { /* weiter */ }
  }

  // 2. Generische Text-Match auf sichtbare Buttons / Links
  const clicked = await page.evaluate((patterns) => {
    const candidates = [
      ...document.querySelectorAll('button, a[role="button"], [role="button"], input[type="button"], input[type="submit"]')
    ];
    // Serialisierbare Patterns als Strings übergeben und im Browser rebuilden
    for (const el of candidates) {
      const text = (el.innerText || el.value || el.getAttribute('aria-label') || '').trim();
      if (!text) continue;
      const rect = el.getBoundingClientRect();
      if (rect.width === 0 || rect.height === 0) continue; // unsichtbar
      for (const pat of patterns) {
        const re = new RegExp(pat.source, pat.flags);
        if (re.test(text)) {
          el.click();
          return true;
        }
      }
    }
    return false;
  }, ACCEPT_TEXT_PATTERNS.map(p => ({ source: p.source, flags: p.flags })));

  return clicked;
}

(async () => {
  let browser;
  try {
    browser = await puppeteer.launch({
      executablePath: execPath,
      headless: true,
      args: [
        '--no-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu',
        '--disable-extensions',
        '--disable-software-rasterizer',
        `--window-size=${vpWidth},${vpHeight}`,
        '--lang=de-DE,de',
        '--disable-features=TranslateUI',
      ],
    });

    const page = await browser.newPage();
    await page.setViewport({ width: vpWidth, height: vpHeight });
    // Deutsche Sprache simulieren — hilft bei Cookie-Bannern die Sprache erkennen
    await page.setExtraHTTPHeaders({ 'Accept-Language': 'de-DE,de;q=0.9' });

    // Seite laden
    await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });

    // Cookie-Banner wegklicken
    const dismissed = await dismissCookieBanner(page);

    if (dismissed) {
      // Warten bis Overlay-Animation fertig ist
      await new Promise(r => setTimeout(r, 1200));
    } else {
      // Kurz warten damit lazy-geladene Inhalte erscheinen
      await new Promise(r => setTimeout(r, 800));
    }

    // Screenshot — bis zu 2400px Seitenhöhe erfassen (~3 Viewport-Längen)
    // Viewport bleibt bei 900px für korrekte Desktop-Layout-Darstellung.
    // Der LLM bekommt so die gesamte Seitenstruktur zu sehen, nicht nur Above-the-Fold.
    const pageHeight = await page.evaluate(() => document.documentElement.scrollHeight);
    const captureHeight = Math.min(Math.max(pageHeight, 900), 2400);
    await page.screenshot({
      path: outFile,
      type: 'png',
      clip: { x: 0, y: 0, width: 1280, height: captureHeight },
    });

    await browser.close();
    process.exit(0);
  } catch (err) {
    if (browser) await browser.close().catch(() => {});
    process.stderr.write(err.message + '\n');
    process.exit(1);
  }
})();
