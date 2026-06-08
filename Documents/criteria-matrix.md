# Kriterien-Matrix — LAT Landingpage-Analyse-Tool
**Basis:** Google Search Quality Evaluator Guidelines (SQEG, September 2025)  
**Stand:** Mai 2026  
**Status:** Planungsdokument — Grundlage für Implementierung

---

## Verfügbare Datenquellen

| Kürzel       | Beschreibung                                                                 |
|--------------|------------------------------------------------------------------------------|
| `pageText`   | Bereinigter Seitentext (DOMParser, ~50–80 KB, ohne Scripts/Styles/Nav/Footer) |
| `rawHtml`    | Vollständiges HTML-Dokument (für Meta-Tags, Schema.org, Strukturanalyse)     |
| `gscData`    | Google Search Console: Klicks, Impressions, CTR, Ø Position, Suchanfragen   |
| `serpData`   | DataForSEO SERP: Position, Titel, Beschreibung, Top-10-URLs                  |
| `backlinkData` | DataForSEO Backlinks: Domain Rank, Referring Domains, Spam Score           |
| `psiData`    | Google PageSpeed Insights: LCP, CLS, TBT, FCP, Mobile Score (0–100)         |
| `AI`         | GPT-4.1 (128K Context) — analysiert Text + Kontext-Blöcke                   |

---

## Gewichtungsschema

| Gewicht | Bedeutung           | Beschreibung                                                  |
|---------|---------------------|---------------------------------------------------------------|
| **4**   | Kritisch            | Direkte Lowest-Quality-Signale; Verstöße führen zu Rating-Absturz |
| **3**   | Hoch                | Kernkriterien für Page Quality; großer Einfluss auf PQ-Stufe  |
| **2**   | Mittel (Standard)   | Wichtige Qualitätssignale                                     |
| **1.5** | Ergänzend           | Qualitätsdetails, techische Signale, nuancierte Bewertungen   |

---

## Cluster 1 — Seitenzweck & Seitentyp
*SQEG Sek. 2.2, 2.3, 2.4, 3.1*

| ID   | Name (nicht-Experten-freundlich)                                | Was wird gemessen                              | Datenquellen        | Messart      | SQEG-Ref | Gewicht |
|------|-----------------------------------------------------------------|------------------------------------------------|---------------------|--------------|----------|---------|
| 1.1  | **Erkennbarer Seitenzweck** — Dient die Seite einem klaren, legitimen Nutzen? | Beneficial Purpose vorhanden                   | AI + pageText       | AI           | 2.2      | 3       |
| 1.2  | **Seitentyp-Klassifikation** — Was für eine Seite ist das? (Shop, Blog, Info, Forum…) | Korrekte Typ-Erkennung als Kontext für alle anderen Kriterien | AI + pageText | AI    | 3.1      | 2       |
| 1.3  | **YMYL-Einordnung** — Betrifft die Seite sensible Bereiche (Gesundheit, Finanzen, Recht, Sicherheit)? | YMYL-Kategorie: clear_ymyl / mixed_ymyl / none | AI + URL + pageText | AI      | 2.3      | 3       |
| 1.4  | **Hauptinhalt klar abgegrenzt** — Ist der Kern-Inhalt leicht von Werbung und Navigation trennbar? | MC vs. Ads vs. SC visuell und strukturell erkennbar | AI + rawHtml  | AI + Metrik  | 2.4.1    | 2       |

---

## Cluster 2 — Inhalt & Tiefe (Main Content Quality)
*SQEG Sek. 3.2, 4.1, 4.6.5, 5.2.2, 18.0*

| ID   | Name (nicht-Experten-freundlich)                                | Was wird gemessen                              | Datenquellen            | Messart      | SQEG-Ref | Gewicht |
|------|-----------------------------------------------------------------|------------------------------------------------|-------------------------|--------------|----------|---------|
| 2.1  | **Menschlicher Aufwand erkennbar** — Steckt echte Arbeit und Sorgfalt im Inhalt? | Effort: Detailgrad, Recherche, Struktur         | AI + pageText           | AI           | 3.2      | 2.5     |
| 2.2  | **Originalität** — Eigener Inhalt, nicht kopiert oder mechanisch umformuliert? | Unique value vs. vorhandene Quellen             | AI + pageText           | AI           | 3.2      | 2.5     |
| 2.3  | **Handwerkliche Qualität** — Ist der Inhalt gut formuliert, logisch strukturiert und lesbar? | Talent & Skill: Sprache, Struktur, Präsentation | AI + pageText           | AI           | 3.2      | 2       |
| 2.4  | **Faktische Korrektheit** — Stimmen die Aussagen mit dem anerkannten Expertenkonsens überein? | Accuracy; besonders relevant bei YMYL          | AI + pageText           | AI           | 3.2      | 3       |
| 2.5  | **Themen-Tiefe & Vollständigkeit** — Deckt die Seite das Thema umfassend ab? | Completeness vs. SERP-Benchmark (Top 10)       | AI + pageText + serpData | AI + Metrik  | 4.1      | 1.5     |
| 2.6  | **Kein Füllmaterial** — Steht der Kerninhalt vorne, ohne leere Phrasen oder Padding? | Filler-Content-Anteil; MC prominent?            | AI + pageText           | AI           | 5.2.2    | 1.5     |
| 2.7  | **Kein KI/Massen-Content-Missbrauch** — Wurde Inhalt ohne echten Mehrwert automatisch generiert? | Scaled/AI content abuse signals                | AI + pageText           | AI           | 4.6.5    | 1.5     |
| 2.8  | **Aktualität des Inhalts** — Ist der Inhalt auf dem neuesten Stand? | Freshness: Datum-Meta, GSC-Signale, Zeitbezüge | AI + rawHtml + gscData  | AI + Metrik  | 18.0     | 1.5     |

---

## Cluster 3 — E-E-A-T (Experience · Expertise · Authoritativeness · Trust)
*SQEG Sek. 3.4, 3.4.1*

| ID   | Name (nicht-Experten-freundlich)                                | Was wird gemessen                              | Datenquellen                      | Messart      | SQEG-Ref | Gewicht |
|------|-----------------------------------------------------------------|------------------------------------------------|-----------------------------------|--------------|----------|---------|
| 3.1  | **Eigene Erfahrung (Experience)** — Zeigt der Autor persönliche Erfahrung mit dem Thema? | First-hand experience: Ich-Berichte, Fotos, konkrete Details | AI + pageText  | AI           | 3.4      | 3       |
| 3.2  | **Fachkompetenz (Expertise)** — Ist erkennbare Fachkunde vorhanden, formal oder durch nachgewiesene Praxis? | Expertise-Signale: Qualifikationen, Tiefe, Terminologie | AI + pageText + rawHtml | AI | 3.4 | 3    |
| 3.3  | **Autorität im Thema (Authoritativeness)** — Ist diese Website eine anerkannte Quelle für dieses Thema? | Domain-Autorität: Backlinks, Domain Rank, GSC-Branded-Queries | backlinkData + gscData + AI | AI + Metrik | 3.4 | 3 |
| 3.4  | **Vertrauenswürdigkeit (Trust)** — Macht die Seite insgesamt einen vertrauenswürdigen Eindruck? **(Wichtigstes E-E-A-T-Element)** | Trust: Zusammenführung aller Signale (Transparenz, Sicherheit, Korrektheit, Reputation) | AI + alle Quellen | AI + Metrik | 3.4 | **4** |
| 3.5  | **YMYL: Richtiges E-E-A-T-Profil** — Bei sensiblen Themen: Formal nachweisbare Expertise statt nur Meinung? | YMYL-spezifische E-E-A-T-Anforderung: bei Gesundheit Arzt, bei Finanzen Berater etc. | AI + pageText | AI | 3.4.1 | 3 |

---

## Cluster 4 — Reputation & Transparenz
*SQEG Sek. 2.5.2, 2.5.3, 3.3.1, 3.3.4*

| ID   | Name (nicht-Experten-freundlich)                                | Was wird gemessen                              | Datenquellen            | Messart      | SQEG-Ref   | Gewicht |
|------|-----------------------------------------------------------------|------------------------------------------------|-------------------------|--------------|------------|---------|
| 4.1  | **Website-Reputation** — Hat die Domain einen guten Ruf in ihrem Themenfeld? | Externe Reputation: Domain Rank, Spam Score, SERP-Präsenz | backlinkData + serpData + AI | AI + Metrik | 3.3.1 | 3 |
| 4.2  | **Autor/Creator erkennbar** — Wer hat den Inhalt erstellt, und ist das nachprüfbar? | Creator identity: Autorenname, Bio, Social-Links, About-Seite | AI + rawHtml  | AI + Metrik  | 3.3.4      | 3       |
| 4.3  | **Impressum & rechtliche Angaben** — Sind gesetzlich erforderliche Angaben vorhanden? | Legal info: Impressum, Datenschutz, AGB        | rawHtml                 | Metrik       | 2.5.3      | 2 *(YMYL: 3)* |
| 4.4  | **Kontaktmöglichkeiten** — Kann man das Unternehmen oder den Autor kontaktieren? | Contact info: E-Mail, Telefon, Kontaktformular  | rawHtml                 | Metrik       | 2.5.3      | 2 *(YMYL: 3)* |
| 4.5  | **Wer steckt hinter der Seite?** — Ist die verantwortliche Organisation oder Person transparent benannt? | Accountability: About-Seite, Unternehmensinfo, Inhabernachweis | AI + rawHtml | AI + Metrik | 2.5.2   | 3       |
| 4.6  | **Interessenkonflikt offengelegt** — Werden Affiliate-Links, Sponsoring oder Werbung transparent gemacht? | Conflict of interest disclosure                | AI + pageText + rawHtml | AI           | 3.4        | 3       |

---

## Cluster 5 — Schaden & Täuschung (Lowest Quality Signals)
*SQEG Sek. 4.2–4.6*

| ID   | Name (nicht-Experten-freundlich)                                | Was wird gemessen                              | Datenquellen                  | Messart      | SQEG-Ref   | Gewicht |
|------|-----------------------------------------------------------------|------------------------------------------------|-------------------------------|--------------|------------|---------|
| 5.1  | **Kein täuschendes Design** — Wird der Nutzer durch Layout, Gestaltung oder falsche Versprechen getäuscht? | Deceptive design: Clickbait, irreführende Optik | AI + rawHtml                 | AI           | 4.5.3      | **4**   |
| 5.2  | **Hauptinhalt zugänglich** — Ist der Inhalt nicht durch Werbung verdeckt oder schwer erreichbar? | MC accessibility: CLS, intrusive interstitials  | AI + rawHtml + psiData (CLS)  | AI + Metrik  | 4.5.4      | 3       |
| 5.3  | **Kein Scam/Spam-Verdacht** — Gibt es Anzeichen von Betrug, Irreführung oder Spam? | Scam signals: Spam Score, verdächtige Muster   | AI + backlinkData (Spam Score) | AI + Metrik | 4.5.5      | **4**   |
| 5.4  | **Keine schädlichen Inhalte** — Kann der Inhalt Nutzern oder Gruppen schaden? | Harmful content: gefährliche Anleitungen, Hassrede | AI + pageText              | AI           | 4.2–4.3    | **4**   |
| 5.5  | **Keine gefährlichen Fehlinformationen** — Werden nachweislich falsche und gefährliche Informationen verbreitet? | Harmful misinformation; Gewicht erhöht bei YMYL | AI + pageText               | AI           | 4.4        | **4**   |
| 5.6  | **Keine Seiten-Kompromittierung** — Gibt es Anzeichen von Hacking, Defacement oder fremden Inhalten? | Hacked/defaced: HTML-Anomalien, injizierte Links | AI + rawHtml                | AI + Metrik  | 4.6.2      | 3       |
| 5.7  | **Keine Domain-Zweckentfremdung** — Passt der aktuelle Inhalt zur Domain-Historie oder wurde sie missbraucht? | Domain abuse: Inhalt vs. Domainname-Erwartung  | AI + serpData               | AI + Metrik  | 4.6.3–4.6.4 | 3      |

---

## Cluster 6 — Technische Qualität & UX
*SQEG Sek. 7.0*

| ID   | Name (nicht-Experten-freundlich)                                | Was wird gemessen                              | Datenquellen          | Messart  | SQEG-Ref | Gewicht |
|------|-----------------------------------------------------------------|------------------------------------------------|-----------------------|----------|----------|---------|
| 6.1  | **Ladegeschwindigkeit & Core Web Vitals** — Wie schnell und stabil lädt die Seite? | LCP (Ladezeit), CLS (Layout-Stabilität), TBT (Reaktionszeit), FCP (erster Inhalt) | psiData | Metrik | 7.0 | 1.5 |
| 6.2  | **Mobile-Tauglichkeit** — Ist die Seite auf Smartphones und Tablets gut nutzbar? | Mobile Performance Score (0–100)               | psiData               | Metrik   | 7.0      | 1.5     |
| 6.3  | **Seitentitel & Meta-Description** — Sind Titel und Beschreibung korrekt, beschreibend und nicht irreführend? | Title-Tag + Meta Description: Länge, Relevanz, Übereinstimmung mit Inhalt | rawHtml + serpData | Metrik | 3.1 | 2 |
| 6.4  | **Strukturierte Daten (Schema.org)** — Sind maschinenlesbare Informationen korrekt eingebettet? | Schema.org: Article, Product, Organization, BreadcrumbList, FAQ etc. | rawHtml | Metrik | 7.0 | 1.5 |
| 6.5  | **HTTPS & Verbindungssicherheit** — Ist die Seite über eine verschlüsselte Verbindung erreichbar? | HTTPS-Protokoll im URL                         | URL                   | Metrik   | 4.5.5    | 2       |

---

## Cluster 7 — Werbung & Supplementary Content
*SQEG Sek. 2.4.2, 2.4.3, 2.4.4, 5.3*

| ID   | Name (nicht-Experten-freundlich)                                | Was wird gemessen                              | Datenquellen            | Messart      | SQEG-Ref      | Gewicht |
|------|-----------------------------------------------------------------|------------------------------------------------|-------------------------|--------------|---------------|---------|
| 7.1  | **Ergänzender Inhalt sinnvoll** — Unterstützen Sidebar, weiterführende Links und Empfehlungen den Hauptinhalt? | SC quality: Relevanz zum Thema, kein Spam       | AI + rawHtml            | AI           | 2.4.2         | 2       |
| 7.2  | **Werbung klar gekennzeichnet** — Ist bezahlter Inhalt und Werbung als solche erkennbar? | Ad labeling: Sponsored, Anzeige, Werbung        | AI + rawHtml            | AI           | 2.4.3         | 2       |
| 7.3  | **Werbung nicht übermäßig aufdringlich** — Stört Werbung das Lesen oder Navigieren? | Intrusive ads: Anzahl, Platzierung, CLS-Beitrag | AI + psiData (CLS)      | AI + Metrik  | 2.4.4 / 5.3   | 2       |

---

## Cluster 8 — Needs Met (Nutzerbedürfnis trifft Suchabsicht)
*SQEG Sek. 13.0–18.0, 20.0*

| ID   | Name (nicht-Experten-freundlich)                                | Was wird gemessen                              | Datenquellen                    | Messart      | SQEG-Ref | Gewicht |
|------|-----------------------------------------------------------------|------------------------------------------------|---------------------------------|--------------|----------|---------|
| 8.1  | **Suchabsicht getroffen** — Beantwortet die Seite das, was der Nutzer mit seiner Suche meint? | Intent match: Know / Do / Website / Visit query types vs. Seiteninhalt | AI + gscData + serpData | AI + Metrik | 13.0 | **4** |
| 8.2  | **Antwort vollständig** — Wird die Suchanfrage vollständig und befriedigend beantwortet? | Answer completeness vs. SERP-Benchmark (Top 10 Tiefe) | AI + pageText + serpData | AI + Metrik | 13.0 | 3 |
| 8.3  | **Aktualität der Antwort** — Ist die Antwort für zeitkritische Suchanfragen aktuell genug? | Freshness for query: Ereignisse, News, saisonale Themen | AI + gscData + rawHtml | AI + Metrik | 18.0 | 2 |
| 8.4  | **Verständlichkeit für die Zielgruppe** — Ist der Inhalt für die typische Zielgruppe verständlich geschrieben? | Readability: Sprache, Komplexität, Struktur     | AI + pageText                   | AI           | 13.0     | 1.5     |

---

## Zusammenfassung

| Cluster                             | Kriterien | Neu ggü. alt |
|-------------------------------------|-----------|--------------|
| 1 — Seitenzweck & Seitentyp         | 4         | —            |
| 2 — Inhalt & Tiefe                  | 8         | —            |
| 3 — E-E-A-T                         | 5         | —            |
| 4 — Reputation & Transparenz        | 6         | —            |
| 5 — Schaden & Täuschung             | 7         | +2 (5.6, 5.7) |
| 6 — Technische Qualität & UX        | 5         | +2 (6.4, 6.5) |
| 7 — Werbung & SC                    | 3         | +2 (7.2, 7.3) |
| 8 — Needs Met                       | 4         | —            |
| **Gesamt**                          | **42**    | **+6 neu**   |

| Messart        | Anzahl |
|----------------|--------|
| Rein AI        | 21     |
| Rein Metrik    | 9      |
| AI + Metrik    | 12     |

| Gewicht 4 (Kritisch) | 3.4 Trust, 5.1 Deceptive Design, 5.3 Scam, 5.4 Harmful Content, 5.5 Dangerous Misinformation, **8.1 Intent** |
|---|---|
| Gewicht 3 (Hoch)     | 1.1 Seitenzweck, 1.3 YMYL, 2.4 Accuracy, 3.1–3.3 E-E-A-T, 3.5 YMYL-E-E-A-T, 4.1–4.2 Reputation/Creator, 4.5–4.6 Accountability, 5.2 MC-Zugänglichkeit, 5.6–5.7 Domain-Signale, **8.2 Antwort vollständig** |
| Gewicht 2.5          | 2.1 Aufwand, 2.2 Originalität |
| Gewicht 2 (Standard) | 1.2, 1.4, 2.3, 4.3–4.4 *(YMYL: 3)*, 6.3, 6.5, 7.1–7.3, **8.3 Aktualität der Antwort** |
| Gewicht 1.5 (Ergänzend) | 2.5–2.8, 6.1–6.2, 6.4, 8.4 |

---

## Offene Implementierungsfragen (Backlog)

| # | Thema | Beschreibung | Priorität |
|---|-------|--------------|-----------|
| I-1 | Schema.org-Parsing | rawHtml via DOMParser → JSON-LD / Microdata extrahieren für 6.4 | Mittel |
| I-2 | HTTPS-Check | URL.startsWith('https://') bereits im Browser prüfbar für 6.5 | Niedrig |
| I-3 | SERP-Tiefe für 2.5/8.2 | Top-10-Wortanzahl vs. eigene Seite als Benchmark | Mittel |
| I-4 | PQ-Stufenausgabe | Lowest / Low / Medium / High / Highest als offizielles SQEG-Label ergänzen | Hoch |
| I-5 | ID-Mapping | Alte IDs c1–c29 auf neue 1.1–8.4 migrieren | Hoch (vor nächstem Deploy) |
| I-6 | GSC-Branded-Queries | Branded-Query-Anteil aus gscData für Autoritäts-Signal (3.3) auswerten | Mittel |
| I-7 | YMYL als Score-Multiplikator | 1.3 (YMYL-Einordnung) nicht als additiver Score, sondern als Kontext-Flag implementieren: Bei clear_ymyl → Gewichte von 2.4, 3.2, 3.5, 4.3, 4.4 auf nächste Stufe anheben; bei mixed_ymyl → halbe Eskalation | Hoch |
