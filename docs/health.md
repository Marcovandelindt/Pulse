# Feature: Health

Dagelijkse stappen bijhouden met streaks, doelen en statistieken.
Één entry per dag. Geen hartslag — alleen stappen en een optionele notitie.

---

## Data

### Tabel: `health_entries`

| Kolom      | Type              | Eigenschappen          |
|------------|-------------------|------------------------|
| `id`       | bigint            | primary key            |
| `date`     | date              | unique, not null       |
| `steps`    | unsigned integer  | nullable               |
| `notes`    | text              | nullable               |
| `created_at` / `updated_at` | timestamp | standaard |

Index op `date`.

---

## Model: `HealthEntry`

```php
// Casts
protected function casts(): array
{
    return [
        'date' => 'date',
        'steps' => 'integer',
    ];
}

// Scopes
public function scopeRecent(Builder $query, int $limit = 10): Builder
public function scopeThisWeek(Builder $query): Builder
public function scopeThisMonth(Builder $query): Builder
public function scopeBetween(Builder $query, Carbon $start, Carbon $end): Builder

// Methods
public function meetsStepGoal(): bool   // vergelijkt met Setting::stepGoal()
public static function stepGoal(): int  // haalt doel op uit settings (default 10000)
```

---

## Architecture

```
app/
├── Actions/Health/
│   ├── CreateHealthEntry.php
│   ├── UpdateHealthEntry.php
│   └── DeleteHealthEntry.php
├── Http/
│   ├── Controllers/Health/
│   │   ├── HealthEntryController.php   (index, store, update, destroy)
│   │   ├── HealthStatsController.php   (index)
│   │   └── HealthExportController.php  (index → CSV download)
│   └── Requests/Health/
│       ├── StoreHealthEntryRequest.php
│       └── UpdateHealthEntryRequest.php
└── Models/
    └── HealthEntry.php
```

### DTO

```php
final readonly class HealthEntryData
{
    public function __construct(
        public Carbon $date,
        public ?int   $steps,
        public ?string $notes,
    ) {}

    public static function fromRequest(StoreHealthEntryRequest $request): self
}
```

### Validatieregels (beide requests identiek, update gebruikt `sometimes`)

```php
'date'  => ['required', 'date', 'before_or_equal:today'],
'steps' => ['nullable', 'integer', 'min:0', 'max:100000'],
'notes' => ['nullable', 'string', 'max:1000'],
```

`StoreHealthEntryRequest` voegt toe: `'date' => ['unique:health_entries,date']`

---

## Routes

```php
Route::prefix('health')->name('health.')->group(function () {
    Route::get('/',              [HealthEntryController::class, 'index'])->name('index');
    Route::post('/',             [HealthEntryController::class, 'store'])->name('store');
    Route::patch('/{entry}',     [HealthEntryController::class, 'update'])->name('update');
    Route::delete('/{entry}',    [HealthEntryController::class, 'destroy'])->name('destroy');
    Route::get('/stats',         [HealthStatsController::class, 'index'])->name('stats');
    Route::get('/export',        [HealthExportController::class, 'index'])->name('export');
});
```

---

## Views

### `pages/health/index.blade.php` — Maandoverzicht

- Maandnavigatie (vorige / huidige / volgende maand via `?month=Y-m`)
- Vier stat-cards bovenaan:
  - Entries deze maand
  - Gemiddelde stappen/dag
  - Doel gehaald (X van Y dagen)
  - Huidige streak
- Kalenderweergave met per dag: stappencount + kleur (groen = doel gehaald, grijs = niet)
- "Add entry" knop → modal
- Klikken op bestaande dag → edit modal

### `pages/health/stats.blade.php` — Statistieken

- Periodefilter: All / 7 days / 30 days / 90 days / Custom
- Secties:
  - **Weekly comparison** — gem. stappen deze week vs vorige week (% verschil)
  - **Monthly comparison** — gem. stappen deze maand vs vorige maand (% verschil)
  - **Step goal** — achievement rate als progressbalk
  - **Streaks** — huidige streak + langste streak
  - **Weekday patterns** — gem. stappen per weekdag (Ma–Zo) als staafgrafiek
  - **Monthly history** — tabel met per maand: entries, totaal stappen, gem. stappen

---

## Statistieken

Alle percentagevergelijkingen op basis van **gemiddelde** stappen per dag (niet totaal),
zodat een lopende week/maand eerlijk vergelijkt met een volledige periode.

### Weekly comparison
```php
$thisWeekAvg  = HealthEntry::thisWeek()->withSteps()->avg('steps');
$lastWeekAvg  = HealthEntry::lastWeek()->withSteps()->avg('steps');
$percentChange = $lastWeekAvg > 0
    ? round((($thisWeekAvg - $lastWeekAvg) / $lastWeekAvg) * 100, 1)
    : 0;
```

### Streaks
Aaneengesloten dagen waarop het stappensdoel gehaald is.
- **Current streak**: tel terug vanaf vandaag of gisteren
- **Longest streak**: over alle data

---

## Export (CSV)

Bestandsnaam: `health-steps-{start}-to-{end}.csv`

Kolommen: `date`, `day_of_week`, `steps`, `goal` (stappensdoel), `goal_met` (yes/no), `notes`

---

## Settings integratie

Het stappensdoel wordt opgehaald uit een `settings`-tabel:

```php
// Setting::get('health.step_goal', default: 10000)
```

Als de `settings`-module nog niet bestaat, hardcoded op `10000`.

---

## Implementatievolgorde

```
1.  php artisan make:model HealthEntry -mfs
2.  Migratie schrijven (zie schema hierboven)
3.  Model invullen (casts, scopes, meetsStepGoal, stepGoal)
4.  HealthEntryData DTO aanmaken
5.  php artisan make:request Health/StoreHealthEntryRequest
6.  php artisan make:request Health/UpdateHealthEntryRequest
7.  Actions aanmaken (Create, Update, Delete)
8.  php artisan make:controller Health/HealthEntryController
9.  php artisan make:controller Health/HealthStatsController
10. php artisan make:controller Health/HealthExportController
11. Routes toevoegen
12. Views bouwen (index eerst, daarna stats)
13. SCSS: resources/css/features/_health.scss aanmaken + importeren
14. php artisan make:test Health/HealthEntryTest --pest
15. vendor/bin/pint --dirty
```
