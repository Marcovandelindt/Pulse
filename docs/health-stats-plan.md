# Health Statistics — Uitbreidingsplan

Gefaseerde uitbreiding van de stats-pagina (`/health/stats`).
Alle berekeningen zijn gebaseerd op de bestaande `steps` + `date` data in `health_entries`.

Kilometerberekening: `stappen × 0,00075 km` (gemiddeld 75 cm per stap).

---

## Fase 1 — All-time totalen & afstand

**Commit samen:** controller-data + view-sectie + SCSS

### Stat-cards (hero-balk bovenaan de pagina)

| Kaart | Waarde |
|---|---|
| Totaal stappen ooit | `SUM(steps)` over alle entries |
| Totaal km ooit | totaal stappen × 0,00075 |
| Totaal km dit jaar | stappen × 0,00075, gefilterd op huidig jaar |
| Dagen gelogd ooit | `COUNT(entries with steps)` |

### Afstandsvergelijkingen

Een blok met herkenbare referentiepunten op basis van totale km ooit:

- Amsterdam → Parijs: 500 km
- Amsterdam → Rome: 1.750 km  
- Amsterdam → Moskou: 2.500 km
- Omtrek van Nederland: 1.075 km
- Omtrek van de Aarde: 40.075 km

Weergave: voor elk referentiepunt tonen hoeveel keer je die afstand hebt gelopen (bijv. "3,4×"), of als percentage als het nog niet gehaald is.

---

## Fase 2 — Persoonlijke records

**Commit samen:** controller-data + view-sectie

| Record | Berekening |
|---|---|
| Beste dag ooit | `MAX(steps)` + bijbehorende datum |
| Beste week ooit | `SUM(steps)` gegroepeerd per ISO-week, dan `MAX` |
| Beste maand ooit | `SUM(steps)` gegroepeerd per maand, dan `MAX` |
| Meest actieve dag van de week | bestaande weekdayPatterns uitbreiden met "beste dag" |

Weergave: prominente kaarten met het getal, de datum/periode en (waar relevant) vergelijking met het huidige doel.

---

## Fase 3 — Doelprestaties uitgebreid

**Commit samen:** controller-data + view-aanpassingen op bestaande sectie

Huidige sectie uitbreiden met:

- **Doel per maand (grafiek)** — staafdiagram van de afgelopen 12 maanden: welk percentage van de dagen was het doel gehaald?
- **Kwaliteitslagen** — aantal dagen boven 150% van het doel, boven 200%, onder 50%
- **Gemiddelde op goede vs slechte dagen** — gemiddeld aantal stappen op dagen dat het doel wél / niet gehaald werd

---

## Fase 4 — Stappenverdeling (histogram)

**Commit samen:** controller-data + view-sectie + eventuele SCSS

Staafdiagram met buckets op basis van alle entries:

| Bucket | |
|---|---|
| 0 – 2.500 | |
| 2.500 – 5.000 | |
| 5.000 – 7.500 | |
| 7.500 – 10.000 | ← doelzone (afhankelijk van ingesteld doel) |
| 10.000 – 12.500 | |
| 12.500+ | |

De bucket die het huidige doel bevat krijgt een accentkleur. Weergave: aantal dagen per bucket + percentage van totaal.

---

## Fase 5 — Consistentie

**Commit samen:** controller-data + view-sectie

| Statistiek | Berekening |
|---|---|
| Logpercentage | (gelogde dagen / dagen sinds eerste entry) × 100 |
| Langste loggingstreak | langste reeks opeenvolgende dagen mét een entry (ongeacht doel) |
| Gemiddeld gat | gemiddeld aantal dagen tussen twee opeenvolgende entries |
| Totaal gemiste dagen | dagen zonder entry tussen eerste en laatste entry |

---

## Fase 6 — Seizoenspatronen

**Commit samen:** controller-data + view-sectie

- **Per kwartaal** — gemiddelde stappen per Q1/Q2/Q3/Q4, gesplitst per jaar als er meerdere jaren zijn
- **Jaar-op-jaar per maand** — tabel of heatmap: elke rij is een maand (jan–dec), elke kolom een jaar; cel toont gemiddelde stappen of `-` als geen data

---

## Fase 7 — Jaar in review

**Commit samen:** controller-data + view-sectie + SCSS

Een samenvatting van het huidige kalenderjaar in één blok:

| Onderdeel | |
|---|---|
| Totaal stappen dit jaar | |
| Totaal km dit jaar | |
| Doelpercentage dit jaar | |
| Beste maand dit jaar | maandnaam + gemiddeld aantal stappen |
| Slechtste maand dit jaar | |
| Actiefste dag van de week dit jaar | |
| Vergelijking met vorig jaar | % meer of minder stappen (zelfde periode) |

---

## Volgorde & afhankelijkheden

```
Fase 1  →  Fase 2  →  Fase 3  →  Fase 4
                  ↓
             Fase 5  →  Fase 6  →  Fase 7
```

Fase 1 t/m 4 zijn onafhankelijk van elkaar maar logisch opeenvolgend.
Fase 5 t/m 7 kunnen parallel aan 3/4 opgepakt worden.

---

## Uitgangspunten

- Geen nieuwe database-kolommen nodig — alles is afleidbaar uit `steps` + `date`
- Kilometerberekening is een schatting (75 cm per stap), wordt als zodanig getoond
- Streaks en consistentie houden rekening met het actieve stappengoal via `StepGoal`
- Alle controller-logica komt in `HealthStatsController`, eventueel uitgesplitst in private methodes
