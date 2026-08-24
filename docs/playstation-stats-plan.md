# PlayStation Statistics — Uitbreidingsplan

Nieuwe statspagina (`/playstation/stats`) met gefaseerde uitbreiding.
Alle berekeningen zijn gebaseerd op `play_station_sessions`, `play_station_games` en `play_station_trophies`.

---

## Fase 1 — All-time totalen

**Commit samen:** controller + route + view-skeleton + stat-cards

### Stat-cards (hero-balk bovenaan)

| Kaart | Waarde |
|---|---|
| Totaal uren gespeeld | `SUM(duration_minutes) / 60` over alle sessies |
| Totaal sessies | `COUNT(play_station_sessions)` |
| Games in bibliotheek | `COUNT(play_station_games)` |
| Gemiddelde sessieduur | `AVG(duration_minutes)` geformatteerd als "Xh Ym" |

### Platform-verdeling

Horizontale balk of kleine cards per platform: hoeveel uur op PS5 / PS4 / PS3 / PSVita.
Visueel: gekleurde balk per platform (bestaande `platformColor()` gebruiken).

---

## Fase 2 — Persoonlijke records

**Commit samen:** controller-methode + view-sectie

| Record | Berekening |
|---|---|
| Langste sessie ooit | `MAX(duration_minutes)` + game-naam + datum |
| Meest gespeelde game | game met hoogste `SUM(duration_minutes)` over alle sessies |
| Drukste dag ooit | dag met hoogste `SUM(duration_minutes)` |
| Meeste sessies op één dag | dag met hoogste `COUNT(sessions)` |

Weergave: prominente kaarten met titel, waarde en context (game-naam, datum).

---

## Fase 3 — Speelpatronen

**Commit samen:** controller-methoden + view-sectie + chart-data

- **Actieve dag van de week** — gemiddeld aantal minuten per dag van de week (ma t/m zo), staafdiagram
- **Actief uur van de dag** — heatmap of histogram: op welk uur `started_at` meestal valt (0–23)
- **Maandelijkse trend** — afgelopen 12 maanden: totaal uren per maand, lijndiagram of staafdiagram

---

## Fase 4 — Bibliotheek-analyse

**Commit samen:** controller-methode + view-sectie

- **Backlog-verdeling** — aantal games per `backlog_status` (Not started / In progress / Completed / On hold / Dropped), als visuele balk
- **Gemiddelde completion** — `AVG(completion_percentage)` over alle games
- **Games per platform** — count per PS5/PS4/PS3/PSVita
- **Afgerond vs begonnen** — hoeveel games zijn gestart (in_progress of verder) maar nog niet 100%

---

## Fase 5 — Trophy-statistieken

**Commit samen:** controller-methode + view-sectie

| Stat | Berekening |
|---|---|
| Totaal trophies verdiend | `COUNT(play_station_trophies WHERE is_earned = true)` |
| Platinums | count op type = platinum AND is_earned |
| Goud / Zilver / Brons | idem per type |
| Trophy-progressie per maand | trophies grouped op `earned_at` maand, afgelopen 12 maanden |

Weergave: grote platinumteller (meest prestigieus), breakdown per type met kleurcodering.

---

## Fase 6 — Consistentie & streaks

**Commit samen:** controller-methode + view-sectie

| Statistiek | Berekening |
|---|---|
| Huidige streak | opeenvolgende dagen met minstens één sessie t/m gisteren/vandaag |
| Langste streak ooit | langste reeks opeenvolgende dagen met sessies |
| Totaal dagen gespeeld | `COUNT(DISTINCT DATE(started_at))` |
| Gemiddeld sessies per week | totaal sessies / aantal weken sinds eerste sessie |
| Dagen sinds laatste sessie | `DATEDIFF(now, MAX(started_at))` |

---

## Fase 7 — Jaar in review

**Commit samen:** controller-methode + view-sectie

Samenvatting van het huidige kalenderjaar:

| Onderdeel | |
|---|---|
| Uren gespeeld dit jaar | |
| Sessies dit jaar | |
| Games gespeeld dit jaar | unieke games met sessie in huidig jaar |
| Beste maand dit jaar | maand met meeste uren |
| Trophies verdiend dit jaar | |
| Nieuwe games gestart | games met eerste sessie in huidig jaar |
| Vergelijking met vorig jaar | % meer of minder uren (zelfde periode) |

---

## Volgorde & afhankelijkheden

```
Fase 1  →  Fase 2  →  Fase 3
                  ↓
             Fase 4  →  Fase 5
                  ↓
             Fase 6  →  Fase 7
```

---

## Uitgangspunten

- Geen nieuwe database-kolommen nodig — alles afleidbaar uit bestaande tabellen
- Sessiedata is de primaire bron voor tijd-gebaseerde stats
- Trophy-data alleen gebruiken als `is_earned = true`
- Controller-logica in `PlayStationStatsController`, uitgesplitst in private methodes
- Route toevoegen als `GET /playstation/stats` vóór de `/{id}` wildcard
