/**
 * contentfinder_extract.mjs — Content Finder: vollständige Seiteninhalt-Extraktion
 *
 * Aufruf: node contentfinder_extract.mjs <url> [--ocr]
 *
 * Gibt JSON auf stdout aus:
 * {
 *   url: string,
 *   blocks: [{ type, text, src }],
 *   img_ocr_candidates: [{ src, tmpFile }]  // nur bei --ocr
 * }
 *
 * Exit 0 = Erfolg
 * Exit 1 = Fehler (Fehlermeldung auf stderr)
 */

import puppeteer from 'puppeteer-core';
import { existsSync, writeFileSync } from 'fs';
import { randomBytes } from 'crypto';
import { tmpdir } from 'os';
import { join } from 'path';

const [,, url, flag] = process.argv;
const ocrEnabled = flag === '--ocr';

if (!url) {
  process.stderr.write('Usage: node contentfinder_extract.mjs <url> [--ocr]\n');
  process.exit(1);
}

// ── Chromium suchen ──────────────────────────────────────────────────────────
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

// ── Cookie-Banner-Selektoren (identisch zu screenshot.mjs) ───────────────────
const ACCEPT_SELECTORS = [
  '#CybotCookiebotDialogBodyLevelButtonLevelOptinAllowAll',
  '#CybotCookiebotDialogBodyButtonAccept',
  '#onetrust-accept-btn-handler',
  '.onetrust-accept-btn-handler',
  '#accept-recommended-btn-handler',
  '[data-testid="uc-accept-all-button"]',
  'button[data-testid="accept-all"]',
  '#cmBtn_1', '.cmpboxbtnyes',
  '#borlabs-cookie-btn-accept-all', '.borlabs-cookie-btn.accept',
  '.klaro .cm-btn.cm-btn-success',
  '.cc-btn.cc-allow', '.cc-accept',
  '#iubenda-cs-accept-btn',
  '#didomi-notice-agree-button',
  '.trustarc-agree-btn',
];
const ACCEPT_TEXT_PATTERNS = [
  { source: 'alle\\s+(akzeptieren|annehmen|zulassen|erlauben)', flags: 'i' },
  { source: 'accept\\s+all', flags: 'i' },
  { source: 'alles\\s+akzeptieren', flags: 'i' },
  { source: 'alle\\s+cookies\\s+akzeptieren', flags: 'i' },
  { source: 'accept\\s+cookies', flags: 'i' },
  { source: 'zustimmen', flags: 'i' },
  { source: 'einverstanden', flags: 'i' },
  { source: 'allow\\s+all', flags: 'i' },
];

async function dismissCookieBanner(page) {
  for (const sel of ACCEPT_SELECTORS) {
    try {
      const el = await page.$(sel);
      if (el) { await el.click(); return; }
    } catch (_) {}
  }
  await page.evaluate((pats) => {
    const candidates = [...document.querySelectorAll('button, a[role="button"], [role="button"]')];
    for (const el of candidates) {
      const text = (el.innerText || el.getAttribute('aria-label') || '').trim();
      if (!text) continue;
      for (const pat of pats) {
        if (new RegExp(pat.source, pat.flags).test(text)) { el.click(); return; }
      }
    }
  }, ACCEPT_TEXT_PATTERNS);
}

// ── Hilfsfunktion: Text aus Element (nur direkte Textknoten) ─────────────────
function getDirectText(el) {
  let text = '';
  el.childNodes.forEach(n => { if (n.nodeType === Node.TEXT_NODE) text += n.textContent; });
  return text.replace(/\s+/g, ' ').trim();
}

// ── Hauptprogramm ─────────────────────────────────────────────────────────────
let browser;
try {
  browser = await puppeteer.launch({
    executablePath: execPath,
    headless: true,
    args: [
      '--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu',
      '--disable-extensions', '--disable-software-rasterizer',
      '--window-size=1280,900', '--lang=de-DE,de',
    ],
  });

  const page = await browser.newPage();
  await page.setViewport({ width: 1280, height: 900 });
  await page.setUserAgent(
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ' +
    '(KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36'
  );

  // Zeitüberschreitung: 30 s für Seitenlade + 5 s Ruhezeit
  await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });
  await dismissCookieBanner(page);
  // Kurze Pause für lazy-loaded Inhalte
  await new Promise(r => setTimeout(r, 1500));
  // Bis ans Ende scrollen (lazy loading aktivieren)
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
  await new Promise(r => setTimeout(r, 800));
  await page.evaluate(() => window.scrollTo(0, 0));
  await new Promise(r => setTimeout(r, 500));

  // ── Inhalte extrahieren ───────────────────────────────────────────────────
  const extracted = await page.evaluate(() => {
    const blocks = [];
    const seen = new Set();
    const imgCandidates = [];

    function clean(t) { return (t || '').replace(/\s+/g, ' ').trim(); }

    function addBlock(type, text, src = null) {
      const t = clean(text);
      if (!t || t.length < 2) return;
      // Kurze generische Wörter überspringen (Navigation etc.)
      if (t.length <= 3 && /^[a-z]+$/i.test(t)) return;
      const key = type + '|' + t.toLowerCase();
      if (seen.has(key)) return;
      seen.add(key);
      blocks.push({ type, text: t, src: src || null });
    }

    // Hilfsfunktion: direkte Textknoten eines Elements
    function directText(el) {
      let t = '';
      el.childNodes.forEach(n => { if (n.nodeType === 3) t += n.textContent; });
      return clean(t);
    }

    // 1. Meta-Daten
    const title = document.querySelector('title');
    if (title) addBlock('Meta-Title', title.textContent);

    const metaDesc = document.querySelector('meta[name="description"]');
    if (metaDesc && metaDesc.content) addBlock('Meta-Description', metaDesc.content);

    const h1s = document.querySelectorAll('h1');
    const ogTitle = document.querySelector('meta[property="og:title"]');
    if (ogTitle && ogTitle.content) addBlock('OG-Title', ogTitle.content);

    // 2. Sichtbare Text-Elemente — strukturiert nach Tag
    const blockSelectors = [
      ['H1', 'h1'], ['H2', 'h2'], ['H3', 'h3'], ['H4', 'h4'], ['H5', 'h5'], ['H6', 'h6'],
      ['Absatz', 'p'], ['Liste', 'li'], ['Tabelle', 'td'], ['Tabelle', 'th'],
      ['Zitat', 'blockquote'], ['Code', 'code'],
    ];

    for (const [type, tag] of blockSelectors) {
      document.querySelectorAll(tag).forEach(el => {
        const st = window.getComputedStyle(el);
        if (st.display === 'none' || st.visibility === 'hidden') return;
        const t = clean(el.textContent);
        if (t) addBlock(type, t);
      });
    }

    // 3. Buttons, Links, Labels, Tooltips
    for (const tag of ['button', 'a', 'label']) {
      document.querySelectorAll(tag).forEach(el => {
        const st = window.getComputedStyle(el);
        if (st.display === 'none') return;
        const t = clean(el.textContent);
        if (t && t.length >= 3) addBlock(tag === 'button' ? 'Button' : tag === 'a' ? 'Link' : 'Label', t);
      });
    }

    // 4. ARIA-Labels
    document.querySelectorAll('[aria-label]').forEach(el => {
      const t = clean(el.getAttribute('aria-label') || '');
      if (t) addBlock('ARIA-Label', t);
    });

    // 5. Title-Attribute (Tooltips)
    document.querySelectorAll('[title]').forEach(el => {
      const t = clean(el.getAttribute('title') || '');
      if (t && t.length >= 3) addBlock('Tooltip', t);
    });

    // 6. SVG <title> Elemente
    document.querySelectorAll('svg title').forEach(el => {
      const t = clean(el.textContent);
      if (t) addBlock('SVG-Title', t);
    });

    // 7. Bilder: Alt-Text + Kandidaten für OCR
    document.querySelectorAll('img').forEach(img => {
      const alt = clean(img.alt);
      const title = clean(img.getAttribute('title') || '');
      const src = img.src || img.dataset?.src || '';

      if (alt) addBlock('Bild-Alt', alt, src);
      if (title && title !== alt) addBlock('Bild-Titel', title, src);

      // OCR-Kandidaten: Bilder ohne Alt-Text, die ein plausibles Infografik-Bild sein könnten
      if (!alt && src && (src.startsWith('http') || src.startsWith('//')) && !src.includes('logo') && !src.includes('icon')) {
        const rect = img.getBoundingClientRect();
        if (rect.width >= 100 && rect.height >= 80) {
          imgCandidates.push({ src, width: Math.round(rect.width), height: Math.round(rect.height) });
        }
      }
    });

    // 8. Input-Placeholder
    document.querySelectorAll('input[placeholder], textarea[placeholder]').forEach(el => {
      const t = clean(el.getAttribute('placeholder') || '');
      if (t && t.length >= 3) addBlock('Placeholder', t);
    });

    return { blocks, imgCandidates, pageUrl: window.location.href, internal_links: [] };
  });

  // ── Interne Links extrahieren ─────────────────────────────────────────────
  const internalLinks = await page.evaluate(() => {
    const links = [];
    const seen  = new Set();
    document.querySelectorAll('a[href]').forEach(a => {
      try {
        const href = new URL(a.href);
        // Nur gleiches Origin, keine Anker, keine Binärdateien
        if (href.origin !== location.origin) return;
        const ext = href.pathname.split('.').pop().toLowerCase();
        if (['pdf','jpg','jpeg','png','gif','svg','webp','zip','exe','doc','xls'].includes(ext)) return;
        const clean = href.origin + href.pathname.replace(/\/$/, '');
        if (!seen.has(clean) && clean !== location.origin + location.pathname.replace(/\/$/, '')) {
          seen.add(clean);
          links.push(clean);
        }
      } catch (_) {}
    });
    return links.slice(0, 80); // Max 80 Links pro Seite
  });

  // ── OCR-Kandidaten: Screenshots der Bilder ────────────────────────────────
  const imgOcrFiles = [];
  if (ocrEnabled && extracted.imgCandidates.length > 0) {
    // Maximal 10 Bilder für OCR
    const candidates = extracted.imgCandidates.slice(0, 10);
    for (const img of candidates) {
      try {
        const element = await page.$(`img[src="${img.src}"]`);
        if (!element) continue;
        const box = await element.boundingBox();
        if (!box || box.width < 80 || box.height < 60) continue;
        const tmpFile = join(tmpdir(), 'cf_ocr_' + randomBytes(8).toString('hex') + '.png');
        await page.screenshot({
          path: tmpFile,
          clip: { x: box.x, y: box.y, width: Math.min(box.width, 1200), height: Math.min(box.height, 800) },
        });
        imgOcrFiles.push({ src: img.src, tmpFile });
      } catch (_) {}
    }
  }

  const result = {
    url: extracted.pageUrl,
    blocks: extracted.blocks,
    img_ocr_candidates: imgOcrFiles,
    internal_links: internalLinks,
  };

  process.stdout.write(JSON.stringify(result));
  process.exitCode = 0;

} catch (err) {
  process.stderr.write('Fehler: ' + err.message + '\n');
  process.exit(1);
} finally {
  if (browser) await browser.close().catch(() => {});
}
