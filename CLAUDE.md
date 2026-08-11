# Pulse — Project Guidelines

Dit is de `CLAUDE.md` voor Pulse, een persoonlijk dashboard.
Dit bestand wordt automatisch ingeladen door Claude Code bij elke sessie.

Het bevat zowel de initiële setup-checklist als de standaarden en conventies die gelden voor de volledige levensduur van het project.

---

## Inhoudsopgave

1. [Tech Stack](#1-tech-stack)
2. [Naamgevingsconventies](#2-naamgevingsconventies)
3. [Laravel installatie & structuur](#3-laravel-installatie--structuur)
4. [PHP-standaarden](#4-php-standaarden)
5. [Laravel-architectuur](#5-laravel-architectuur)
6. [Database & Eloquent](#6-database--eloquent)
7. [Frontend — CSS/SCSS met BEM](#7-frontend--cssscss-met-bem)
8. [Frontend — JavaScript](#8-frontend--javascript)
9. [Blade component-systeem](#9-blade-component-systeem)
10. [Dashboard-design](#10-dashboard-design)
11. [Dark mode](#11-dark-mode)
12. [Testing](#12-testing)
13. [Code quality tooling](#13-code-quality-tooling)
14. [Development workflow](#14-development-workflow)

---

## 1. Tech Stack

| Laag | Keuze | Reden |
|---|---|---|
| Backend | PHP 8.3+ / Laravel 12 | Laatste LTS, readonly, enums, match |
| Frontend bundler | Vite 6 | Standaard in Laravel 12 |
| CSS framework | Tailwind CSS v4 | CSS-first config, geen JS config-bestand |
| Custom styling | SCSS + BEM | Gestructureerde component-stijlen naast Tailwind |
| JS interactie | Alpine.js v3 | Lichtgewicht, inline declaratief, geen build-stap nodig |
| Icons | Heroicons (via SVG sprite) | Framework-agnostisch, inline SVG |
| Templating | Blade components | Herbruikbaar UI-systeem |
| Testing | Pest v4 | Expressieve syntax, browser testing |
| Code formatter | Laravel Pint | Opinionated, zero-config |
| Static analysis | PHPStan level 8 | Strikte type-checking |

**Bewust niet gekozen:**
- Livewire — onnodige complexiteit voor een persoonlijke app
- Vue/React — overkill, Alpine.js is voldoende
- Inertia — voegt een router-laag toe die hier geen waarde biedt

---

## 2. Naamgevingsconventies

**Alles is altijd in het Engels.** Dit geldt zonder uitzondering voor:

- PHP: klassen, methoden, properties, variabelen, enums, interfaces
- Database: tabelnamen, kolomnamen, foreign keys, indexen
- Blade: component-namen, view-bestandsnamen, slot-namen, `@props`
- CSS/SCSS: BEM-blokken, modifiers, CSS custom properties
- JavaScript: functies, variabelen, module-namen, `data-*` attributen
- Routes: route-namen en URL-segmenten (`/health`, `health.index`)
- Git: branch-namen, commit messages

```php
// ✅ correct
class HealthEntry extends Model { }
public function calculateWeeklyAverage(): float { }
$stepGoal = 10000;

// ❌ vermijd
class GezondheidsInvoer extends Model { }
public function berekenWekelijksGemiddelde(): float { }
$stappenDoel = 10000;
```

```css
/* ✅ correct */
.stat-card { }
.stat-card__label { }
--color-bg-primary: #0f1117;

/* ❌ vermijd */
.statistiek-kaart { }
.statistiek-kaart__label { }
--kleur-achtergrond: #0f1117;
```

```
# ✅ correct (database)
health_entries, steps, avg_heart_rate, user_id

# ❌ vermijd
gezondheid_invoer, stappen, gemiddeld_hartslag
```

---

## 3. Laravel installatie & structuur

### Installatie

```bash
composer create-project laravel/laravel pulse
cd pulse
composer require laravel/pint --dev
composer require nunomaduro/phpstan-phpstan --dev  # of larastan
npm install
npm install -D sass
npm install alpinejs
```

### Initiële configuratie

```bash
# .env aanpassen
APP_NAME=Pulse
APP_ENV=local
APP_TIMEZONE=Europe/Amsterdam
APP_LOCALE=nl

# Sleutel genereren
php artisan key:generate
```

### Volledige directory structuur

```
app/
├── Actions/                    # Enkelvoudige acties (business logic)
│   ├── Health/
│   ├── Finance/
│   └── Music/
├── Console/
│   └── Commands/
├── Enums/                      # Alle PHP enums
├── Http/
│   ├── Controllers/            # Dun — alleen request/response
│   │   ├── Dashboard/
│   │   ├── Health/
│   │   ├── Finance/
│   │   └── Music/
│   └── Requests/               # Form Requests per feature
│       ├── Health/
│       └── Finance/
├── Models/                     # Eloquent models + concerns
│   └── Concerns/
├── Providers/
│   └── AppServiceProvider.php
├── Services/                   # Externe API-integraties
│   ├── Spotify/
│   ├── Steam/
│   └── TMDB/
└── View/
    └── Composers/              # View composers voor gedeelde data

resources/
├── css/
│   ├── app.css                 # Tailwind entry + @import chain
│   ├── base/
│   │   ├── _reset.css
│   │   ├── _typography.css
│   │   └── _variables.css      # CSS custom properties (tokens)
│   ├── components/             # BEM-component stijlen
│   │   ├── _button.scss
│   │   ├── _card.scss
│   │   ├── _badge.scss
│   │   ├── _table.scss
│   │   ├── _modal.scss
│   │   ├── _sidebar.scss
│   │   ├── _nav.scss
│   │   ├── _form.scss
│   │   └── _stat-card.scss
│   └── features/               # Feature-specifieke stijlen
│       ├── _dashboard.scss
│       ├── _health.scss
│       └── _finance.scss
├── js/
│   ├── app.js                  # Alpine init + module imports
│   └── modules/                # Losse JS-modules per concern
│       ├── charts.js
│       ├── modal.js
│       └── sidebar.js
└── views/
    ├── components/             # Blade components
    │   ├── ui/                 # Generieke UI-bouwstenen
    │   │   ├── button.blade.php
    │   │   ├── badge.blade.php
    │   │   ├── card.blade.php
    │   │   ├── modal.blade.php
    │   │   ├── table.blade.php
    │   │   ├── empty-state.blade.php
    │   │   └── avatar.blade.php
    │   ├── form/
    │   │   ├── input.blade.php
    │   │   ├── select.blade.php
    │   │   ├── textarea.blade.php
    │   │   └── error.blade.php
    │   ├── stats/
    │   │   ├── stat-card.blade.php
    │   │   └── trend-badge.blade.php
    │   └── layout/
    │       ├── page-header.blade.php
    │       ├── sidebar.blade.php
    │       └── notification.blade.php
    ├── layouts/
    │   └── app.blade.php
    └── pages/                  # Feature-views (geen losse root-mappen)
        ├── dashboard/
        │   └── index.blade.php
        ├── health/
        │   ├── index.blade.php
        │   └── stats.blade.php
        └── finance/
            └── index.blade.php

routes/
├── web.php                     # Alleen route-definities, geen logica
├── console.php
└── channels.php
```

---

## 3. PHP-standaarden

### Constructor property promotion — altijd

```php
// ✅ correct
final class CreateHealthEntry
{
    public function __construct(
        private readonly HealthEntryRepository $repository,
        private readonly Carbon $date,
    ) {}
}

// ❌ vermijd
class CreateHealthEntry
{
    private HealthEntryRepository $repository;

    public function __construct(HealthEntryRepository $repository)
    {
        $this->repository = $repository;
    }
}
```

### Expliciete return types — altijd

```php
// ✅ correct
public function handle(): HealthEntry { }
public function formatDate(Carbon $date): string { }
public function findAll(): Collection { }
public function delete(): void { }
```

### Enums voor constanten

```php
// app/Enums/TrendDirection.php
enum TrendDirection: string
{
    case Up = 'up';
    case Down = 'down';
    case Same = 'same';

    public function label(): string
    {
        return match($this) {
            self::Up   => 'Stijging',
            self::Down => 'Daling',
            self::Same => 'Gelijk',
        };
    }
}
```

### Match over switch

```php
// ✅ correct
$color = match($trend) {
    TrendDirection::Up   => 'green',
    TrendDirection::Down => 'red',
    TrendDirection::Same => 'gray',
};
```

### Readonly properties voor value objects / DTOs

```php
final readonly class HealthEntryData
{
    public function __construct(
        public int $steps,
        public ?int $avgHeartRate,
        public ?int $restingHeartRate,
        public Carbon $date,
    ) {}
}
```

### Named arguments voor duidelijkheid

```php
// ✅ leesbaar bij meerdere parameters
HealthEntry::create(
    steps: $data->steps,
    date: $data->date,
    avgHeartRate: $data->avgHeartRate,
);
```

### Curly braces — altijd

```php
// ✅ correct
if ($entry->meetsGoal()) {
    $streak++;
}

// ❌ vermijd
if ($entry->meetsGoal())
    $streak++;
```

### Geen inline comments tenzij het WHY niet-obvius is

```php
// ✅ wel — niet-obvius waarom
$totalKm = $steps * 0.00075; // gemiddeld 75 cm per stap

// ❌ niet — legt uit WAT de code doet
$steps = $entry->steps; // steps ophalen
```

---

## 4. Laravel-architectuur

### Thin controllers

Controllers doen alleen: request ontvangen → actie aanroepen → response teruggeven.

```php
// app/Http/Controllers/Health/HealthEntryController.php
final class HealthEntryController extends Controller
{
    public function store(StoreHealthEntryRequest $request, CreateHealthEntry $action): RedirectResponse
    {
        $action->handle(HealthEntryData::fromRequest($request));

        return redirect()->route('health.index')->with('success', 'Entry opgeslagen.');
    }
}
```

### Actions — één verantwoordelijkheid per klasse

```php
// app/Actions/Health/CreateHealthEntry.php
final class CreateHealthEntry
{
    public function handle(HealthEntryData $data): HealthEntry
    {
        return HealthEntry::create([
            'steps'               => $data->steps,
            'avg_heart_rate'      => $data->avgHeartRate,
            'resting_heart_rate'  => $data->restingHeartRate,
            'date'                => $data->date,
        ]);
    }
}
```

### DTOs via `fromRequest()`

```php
final readonly class HealthEntryData
{
    public function __construct(
        public int $steps,
        public ?int $avgHeartRate,
        public ?int $restingHeartRate,
        public Carbon $date,
    ) {}

    public static function fromRequest(StoreHealthEntryRequest $request): self
    {
        return new self(
            steps: (int) $request->validated('steps'),
            avgHeartRate: $request->validated('avg_heart_rate'),
            restingHeartRate: $request->validated('resting_heart_rate'),
            date: Carbon::parse($request->validated('date')),
        );
    }
}
```

### Services — alleen voor externe API's

Services bevatten integratie-logica (Spotify, Steam, TMDB). Geen business logic.

```php
// app/Services/Spotify/SpotifyTrackService.php
final class SpotifyTrackService
{
    public function __construct(
        private readonly SpotifyAuthService $auth,
    ) {}

    public function getCurrentTrack(): ?array
    {
        return $this->auth->getClient()->getMyCurrentPlayingTrack();
    }
}
```

### Form Requests — altijd, nooit inline validatie

```php
// app/Http/Requests/Health/StoreHealthEntryRequest.php
final class StoreHealthEntryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'steps'               => ['required', 'integer', 'min:0'],
            'avg_heart_rate'      => ['nullable', 'integer', 'between:30,250'],
            'resting_heart_rate'  => ['nullable', 'integer', 'between:30,250'],
            'date'                => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'steps.required' => 'Vul het aantal stappen in.',
            'date.before_or_equal' => 'De datum mag niet in de toekomst liggen.',
        ];
    }
}
```

### Routes — gegroepeerd per feature

```php
// routes/web.php
Route::middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('health')->name('health.')->group(function () {
        Route::get('/', [HealthEntryController::class, 'index'])->name('index');
        Route::post('/', [HealthEntryController::class, 'store'])->name('store');
        Route::get('/stats', [HealthStatsController::class, 'index'])->name('stats');
        Route::get('/{entry}/edit', [HealthEntryController::class, 'edit'])->name('edit');
        Route::patch('/{entry}', [HealthEntryController::class, 'update'])->name('update');
        Route::delete('/{entry}', [HealthEntryController::class, 'destroy'])->name('destroy');
    });

});
```

### View Composers — gedeelde sidebar-data etc.

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    View::composer('layouts.app', AppLayoutComposer::class);
}

// app/View/Composers/AppLayoutComposer.php
final class AppLayoutComposer
{
    public function compose(View $view): void
    {
        $view->with('currentTrack', Cache::remember('spotify.current', 30, fn () =>
            app(SpotifyTrackService::class)->getCurrentTrack()
        ));
    }
}
```

---

## 5. Database & Eloquent

### Migraties — altijd alle attributes herhalen bij wijziging

```php
Schema::table('health_entries', function (Blueprint $table) {
    // Altijd alle bestaande column-attributes meenemen bij een change()
    $table->integer('steps')->nullable()->unsigned()->change();
});
```

### Models — casts via methode, niet property

```php
final class HealthEntry extends Model
{
    protected function casts(): array
    {
        return [
            'date'          => 'date',
            'created_at'    => 'datetime',
            'updated_at'    => 'datetime',
        ];
    }
}
```

### Relaties met return type hints

```php
public function entries(): HasMany
{
    return $this->hasMany(HealthEntry::class);
}

public function category(): BelongsTo
{
    return $this->belongsTo(ExpenseCategory::class);
}
```

### Geen N+1 — eager loading in controller

```php
// ✅ correct
$entries = HealthEntry::with('category')->orderByDesc('date')->paginate(25);

// ❌ vermijd
$entries = HealthEntry::all(); // lazy loads relations in loop
```

### Scopes voor herbruikbare queries

```php
public function scopeThisMonth(Builder $query): Builder
{
    return $query->whereMonth('date', now()->month)
                 ->whereYear('date', now()->year);
}

public function scopeWithSteps(Builder $query): Builder
{
    return $query->whereNotNull('steps');
}
```

### Geen `DB::` buiten migraties

```php
// ✅ correct
HealthEntry::query()->whereMonth('date', now()->month)->avg('steps');

// ❌ vermijd
DB::table('health_entries')->whereMonth('date', now()->month)->avg('steps');
```

---

## 6. Frontend — CSS/SCSS met BEM

### Aanpak

Tailwind v4 verzorgt layout, spacing, kleuren en utility-patronen.
SCSS met **BEM** verzorgt custom component-stijlen die te complex zijn voor utilities alleen.

### Tailwind v4 configuratie (CSS-first)

```css
/* resources/css/app.css */
@import "tailwindcss";
@import "./base/variables";
@import "./base/typography";
@import "./components/button";
@import "./components/card";
@import "./components/badge";
@import "./components/stat-card";
@import "./components/sidebar";
@import "./components/modal";
@import "./components/table";
@import "./components/form";
@import "./features/dashboard";
@import "./features/health";

@theme {
    --color-brand:        oklch(0.60 0.18 260);
    --color-brand-light:  oklch(0.75 0.14 260);
    --color-surface:      var(--bg-secondary);
    --font-sans:          'Inter', system-ui, sans-serif;
    --radius-card:        0.75rem;
}
```

### CSS custom properties als design tokens

```scss
/* resources/css/base/_variables.scss */
:root {
    /* Kleuren */
    --color-bg-primary:    #0f1117;
    --color-bg-secondary:  #1a1d27;
    --color-bg-tertiary:   #21253a;
    --color-border:        rgba(255, 255, 255, 0.08);
    --color-text-primary:  #f1f5f9;
    --color-text-muted:    #64748b;

    /* Spacing */
    --space-page:    1.5rem;

    /* Borders */
    --radius-sm:   0.375rem;
    --radius-md:   0.5rem;
    --radius-lg:   0.75rem;
    --radius-xl:   1rem;

    /* Shadows */
    --shadow-card: 0 1px 3px rgba(0, 0, 0, 0.3), 0 1px 2px rgba(0, 0, 0, 0.2);

    /* Transitions */
    --transition-base: 150ms ease;
}
```

### BEM-methodologie

**Block** = onafhankelijk component (`card`)
**Element** = onderdeel van block (`card__header`, `card__body`)
**Modifier** = variant of staat (`card--compact`, `card--highlighted`)

```scss
/* resources/css/components/_card.scss */
.card {
    background:    var(--color-bg-secondary);
    border:        1px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow:    var(--shadow-card);

    &__header {
        display:         flex;
        align-items:     center;
        justify-content: space-between;
        padding:         1rem 1.25rem;
        border-bottom:   1px solid var(--color-border);

        &-title {
            font-size:   0.9375rem;
            font-weight: 600;
            color:       var(--color-text-primary);
        }
    }

    &__body {
        padding: 1.25rem;
    }

    &__footer {
        padding:      0.75rem 1.25rem;
        border-top:   1px solid var(--color-border);
        background:   var(--color-bg-tertiary);
        border-radius: 0 0 var(--radius-lg) var(--radius-lg);
    }

    /* Modifier: compacte variant */
    &--compact {
        .card__header,
        .card__body { padding: 0.75rem 1rem; }
    }

    /* Modifier: highlighted (accent border) */
    &--highlighted {
        border-color: var(--color-brand);
    }
}
```

```scss
/* resources/css/components/_stat-card.scss */
.stat-card {
    background:    var(--color-bg-secondary);
    border:        1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding:       1.25rem;
    display:       flex;
    gap:           1rem;
    align-items:   flex-start;

    &__icon {
        width:         2.5rem;
        height:        2.5rem;
        border-radius: var(--radius-md);
        display:       flex;
        align-items:   center;
        justify-content: center;
        flex-shrink:   0;
    }

    &__content {
        flex: 1;
        min-width: 0;
    }

    &__label {
        font-size:   0.75rem;
        font-weight: 500;
        color:       var(--color-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
    }

    &__value {
        font-size:   1.5rem;
        font-weight: 700;
        color:       var(--color-text-primary);
        line-height: 1;
    }

    &__trend {
        margin-top:  0.375rem;
        font-size:   0.8125rem;
        display:     flex;
        align-items: center;
        gap:         0.25rem;
    }
}
```

```scss
/* resources/css/components/_button.scss */
.btn {
    display:         inline-flex;
    align-items:     center;
    gap:             0.375rem;
    padding:         0.5rem 1rem;
    border-radius:   var(--radius-md);
    font-size:       0.875rem;
    font-weight:     500;
    line-height:     1;
    cursor:          pointer;
    border:          1px solid transparent;
    transition:      background var(--transition-base), border-color var(--transition-base);
    white-space:     nowrap;

    &--primary {
        background:   var(--color-brand);
        color:        #fff;

        &:hover { filter: brightness(1.1); }
    }

    &--secondary {
        background:   var(--color-bg-tertiary);
        color:        var(--color-text-primary);
        border-color: var(--color-border);

        &:hover { border-color: rgba(255, 255, 255, 0.2); }
    }

    &--danger {
        background: transparent;
        color:      #ef4444;
        border-color: rgba(239, 68, 68, 0.3);

        &:hover { background: rgba(239, 68, 68, 0.1); }
    }

    &--sm {
        padding:   0.375rem 0.625rem;
        font-size: 0.8125rem;
    }

    &--icon {
        padding: 0.5rem;
        width:   2.25rem;
        height:  2.25rem;
        justify-content: center;
    }
}
```

### Regels voor SCSS-bestanden

- Eén bestand per component (`_card.scss`, `_button.scss`)
- Feature-stijlen in `features/` — alleen feature-specifieke layout/variaties
- Geen `!important`
- Geen hardcoded kleuren — altijd `var(--color-*)` gebruiken
- Nesting maximaal 3 levels diep

---

## 7. Frontend — JavaScript

### Alpine.js als standaard

Alpine.js vervangt jQuery en ad-hoc inline JS. Declaratief, component-gebaseerd.

```html
<!-- Modal met Alpine -->
<div x-data="{ open: false }">
    <button @click="open = true" class="btn btn--primary">Toevoegen</button>

    <div x-show="open" x-transition @keydown.escape.window="open = false"
         class="modal" style="display:none;">
        <div class="modal__backdrop" @click="open = false"></div>
        <div class="modal__panel">
            <!-- inhoud -->
        </div>
    </div>
</div>
```

### Initialisatie in app.js

```js
// resources/js/app.js
import Alpine from 'alpinejs';
import { initCharts } from './modules/charts.js';

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
});
```

### JS-modules — één concern per bestand

```js
// resources/js/modules/charts.js
export function initCharts() {
    document.querySelectorAll('[data-chart]').forEach(el => {
        const type = el.dataset.chart;
        const data = JSON.parse(el.dataset.chartData ?? '{}');
        renderChart(el, type, data);
    });
}

function renderChart(el, type, data) {
    // chart rendering logic
}
```

### Data doorgeven vanuit Blade

```blade
{{-- Geen inline JS-variabelen of window.x = ... --}}
<canvas
    data-chart="line"
    data-chart-data="{{ json_encode($chartData) }}"
></canvas>
```

### Regels voor JavaScript

- Geen inline `<script>` in views — alles in modules
- Geen `var` — altijd `const` of `let`
- Geen jQuery
- Complexe logica in Alpine `x-data` components registreren als `Alpine.data()`
- Geen `window.*` globals behalve `window.Alpine`

---

## 8. Blade component-systeem

### Anonieme components voor UI-bouwstenen

```blade
{{-- resources/views/components/ui/card.blade.php --}}
@props([
    'title'   => null,
    'compact' => false,
    'class'   => '',
])

<div {{ $attributes->class(['card', 'card--compact' => $compact, $class]) }}>
    @if($title)
        <div class="card__header">
            <span class="card__header-title">{{ $title }}</span>
            @isset($action)
                <div>{{ $action }}</div>
            @endisset
        </div>
    @endif

    <div class="card__body">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="card__footer">{{ $footer }}</div>
    @endisset
</div>
```

```blade
{{-- resources/views/components/stats/stat-card.blade.php --}}
@props([
    'label'       => '',
    'value'       => '—',
    'icon'        => null,
    'iconColor'   => 'brand',
    'trend'       => null,   // percentage als float, bijv. 14.2
    'trendLabel'  => null,
])

<div class="stat-card">
    @if($icon)
        <div class="stat-card__icon" style="background: rgba(var(--color-{{ $iconColor }}-rgb), 0.15);">
            <x-icon :name="$icon" class="w-5 h-5" />
        </div>
    @endif

    <div class="stat-card__content">
        <div class="stat-card__label">{{ $label }}</div>
        <div class="stat-card__value">{{ $value }}</div>

        @if($trend !== null)
            <div class="stat-card__trend">
                <x-stats.trend-badge :trend="$trend" :label="$trendLabel" />
            </div>
        @endif
    </div>
</div>
```

```blade
{{-- resources/views/components/stats/trend-badge.blade.php --}}
@props(['trend' => 0, 'label' => null])

@php
    $isUp   = $trend > 0;
    $isDown = $trend < 0;
    $color  = $isUp ? 'text-green-400' : ($isDown ? 'text-red-400' : 'text-gray-400');
    $icon   = $isUp ? 'arrow-up' : ($isDown ? 'arrow-down' : 'minus');
@endphp

<span class="inline-flex items-center gap-1 text-xs font-medium {{ $color }}">
    <x-icon :name="$icon" class="w-3 h-3" />
    {{ abs($trend) }}%
    @if($label)
        <span class="text-[var(--color-text-muted)]">{{ $label }}</span>
    @endif
</span>
```

### Gebruik in views

```blade
{{-- pages/dashboard/index.blade.php --}}
<x-layouts.app title="Dashboard">

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stats.stat-card
            label="Stappen vandaag"
            :value="number_format($todaySteps)"
            icon="heart"
            :trend="$stepsTrend"
            trend-label="vs gisteren"
        />
        <x-stats.stat-card
            label="Uitgaven deze maand"
            :value="'€ ' . number_format($monthExpenses, 2, ',', '.')"
            icon="credit-card"
            :trend="$expensesTrend"
            trend-label="vs vorige maand"
        />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Recente activiteit">
            {{-- inhoud --}}
        </x-ui.card>
    </div>

</x-layouts.app>
```

### Layout component

```blade
{{-- resources/views/components/layouts/app.blade.php --}}
@props(['title' => 'Pulse'])

<!DOCTYPE html>
<html lang="nl" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Pulse</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] antialiased">

    <div class="layout">
        <x-layout.sidebar />

        <main class="layout__main">
            <div class="layout__content">
                {{ $slot }}
            </div>
        </main>
    </div>

</body>
</html>
```

---

## 9. Dashboard-design

Het dashboard is het startpunt. Het moet in één oogopslag de meest relevante informatie tonen.

### Structuur

```
┌─────────────────────────────────────────────────────────────┐
│ SIDEBAR  │  PAGE HEADER (Goedemorgen, Marco)                │
│          │─────────────────────────────────────────────────│
│  Nav     │  STATS ROW  [stappen] [slaap] [uitgaven] [music] │
│  items   │─────────────────────────────────────────────────│
│          │  COL 1 (2/3)              COL 2 (1/3)           │
│          │  Activiteit grafiek       Nu aan het luisteren   │
│          │                           Recent gespeeld        │
│          │─────────────────────────────────────────────────│
│          │  COL 1 (1/2)              COL 2 (1/2)           │
│          │  Laatste uitgaven         Health streak          │
└─────────────────────────────────────────────────────────────┘
```

### Ontwerp-principes voor het dashboard

- **Geen tabel als eerste indruk** — grafieken en stat-cards voor de fold
- **Maximaal 4 stat-cards** bovenaan — focus, geen information overload
- **Wit ruimte** — `gap-6` tussen secties, niet alles volproppen
- **Consistente card-hoogte** in een rij — gebruik `items-start` of vaste `min-height`
- **Trending indicators** op elke metric die vergeleken kan worden
- **Empty states** voor elke sectie wanneer er nog geen data is

### Sidebar-structuur

```blade
{{-- Gegroepeerd per domein, niet alfabetisch --}}
<nav class="sidebar__nav">
    <div class="sidebar__group">
        <span class="sidebar__group-label">Overzicht</span>
        <x-layout.nav-item route="dashboard" icon="home" label="Dashboard" />
    </div>

    <div class="sidebar__group">
        <span class="sidebar__group-label">Lifestyle</span>
        <x-layout.nav-item route="health.index"  icon="heart"        label="Gezondheid" />
        <x-layout.nav-item route="music.index"   icon="musical-note" label="Muziek" />
    </div>

    <div class="sidebar__group">
        <span class="sidebar__group-label">Financiën</span>
        <x-layout.nav-item route="finance.index" icon="credit-card"  label="Uitgaven" />
    </div>
</nav>
```

---

## 10. Dark mode

Dark mode is de standaard (niet optioneel). Geen toggle nodig.

```css
/* Alle kleuren via CSS custom properties — geen Tailwind dark: prefix nodig */
:root {
    --color-bg-primary:   #0f1117;
    --color-bg-secondary: #1a1d27;
    --color-text-primary: #f1f5f9;
}
```

Als later toch een light mode gewenst is:

```css
[data-theme="light"] {
    --color-bg-primary:   #f8fafc;
    --color-bg-secondary: #ffffff;
    --color-text-primary: #0f172a;
}
```

```blade
{{-- Toggle in blade --}}
<html lang="nl" x-data x-bind:data-theme="$store.theme.current">
```

---

## 11. Testing

### Pest v4 — standaard setup

```php
// tests/Feature/Health/HealthEntryTest.php
use App\Models\HealthEntry;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can store a health entry', function () {
    $response = $this->post(route('health.store'), [
        'steps'    => 8500,
        'date'     => today()->format('Y-m-d'),
    ]);

    $response->assertRedirect(route('health.index'));
    $this->assertDatabaseHas('health_entries', ['steps' => 8500]);
});

it('requires steps to be positive', function () {
    $this->post(route('health.store'), ['steps' => -1])
         ->assertInvalid(['steps']);
});
```

### Factories altijd aanmaken bij een nieuw model

```bash
php artisan make:model HealthEntry -mfs  # model + migration + factory + seeder
```

### Datasets voor validatietests

```php
it('validates step constraints', function (int $steps, bool $valid) {
    $response = $this->post(route('health.store'), ['steps' => $steps, 'date' => today()]);
    $valid
        ? $response->assertValid('steps')
        : $response->assertInvalid('steps');
})->with([
    'zero is valid'     => [0,      true],
    'normal steps'      => [8500,   true],
    'negative invalid'  => [-1,     false],
    'too high invalid'  => [200001, false],
]);
```

---

## 12. Code quality tooling

### Pint — code formatter

```bash
# Altijd draaien voor een commit
vendor/bin/pint --dirty
```

```json
// pint.json
{
    "preset": "laravel",
    "rules": {
        "declare_strict_types": true,
        "final_class": true
    }
}
```

### PHPStan / Larastan

```bash
composer require --dev nunomaduro/larastan
```

```neon
# phpstan.neon
includes:
    - vendor/nunomaduro/larastan/extension.neon

parameters:
    level: 8
    paths:
        - app
```

### `declare(strict_types=1)` in elk PHP-bestand

```php
<?php

declare(strict_types=1);

namespace App\Actions\Health;
```

### `final` op klassen die niet extended worden

```php
// Actions, DTOs, Controllers — altijd final
final class CreateHealthEntry { }
final readonly class HealthEntryData { }
final class HealthEntryController extends Controller { }

// Models — niet final (Eloquent vereist extensibility)
class HealthEntry extends Model { }
```

---

## 13. Development workflow

### Composer scripts

```json
// composer.json
"scripts": {
    "dev":   ["Composer\\Config\\Scripts\\Executor::run", "@php artisan serve", "npm run dev"],
    "setup": [
        "@composer install",
        "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"",
        "@php artisan key:generate",
        "@php artisan migrate --seed",
        "npm install",
        "npm run build"
    ],
    "test":  "@php artisan test",
    "lint":  "vendor/bin/pint --dirty",
    "analyse": "vendor/bin/phpstan analyse"
}
```

### Vite configuratie

```js
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

### Git workflow

- Branch per feature: `feature/health-entries`, `feature/finance-expenses`
- Commit message formaat: `Add health entry creation with streak tracking`
- Geen WIP commits op main

### Volgorde bij het implementeren van een nieuwe feature

```
1. php artisan make:model Feature -mfs
2. Migratie schrijven
3. Model invullen (casts, relaties, scopes)
4. php artisan make:request Feature/StoreFeatureRequest
5. DTO aanmaken (app/Actions/Feature/FeatureData.php)
6. Action aanmaken (app/Actions/Feature/CreateFeature.php)
7. php artisan make:controller Feature/FeatureController
8. Routes toevoegen in routes/web.php
9. Blade views aanmaken in resources/views/pages/feature/
10. SCSS toevoegen in resources/css/features/_feature.scss + importeren in app.css
11. php artisan make:test Feature/FeatureTest
12. vendor/bin/pint --dirty
```

---

## Samenvatting checklist — base setup

> **Scope:** alleen de basis. Geen models, migrations, controllers of routes buiten de home-pagina.
> Features worden daarna één voor één geïmplementeerd aan de hand van de referentiesecties hierboven.

### Laravel & tooling
- [ ] Laravel 12 installeren met PHP 8.3
- [ ] `.env` instellen (`APP_NAME`, `APP_TIMEZONE=Europe/Amsterdam`, `APP_LOCALE=en`)
- [ ] `pint.json` aanmaken met `declare_strict_types` en `final_class` rules
- [ ] `phpstan.neon` aanmaken op level 8
- [ ] Pest v4 geconfigureerd
- [ ] `composer setup` en `composer dev` scripts werkend

### Frontend
- [ ] Tailwind v4 geconfigureerd in `resources/css/app.css`
- [ ] SCSS mappenstructuur aanmaken (`base/`, `components/`, `features/`)
- [ ] Design tokens in `_variables.scss` (kleuren, spacing, radius, shadows)
- [ ] Alpine.js geïnstalleerd en geïnitialiseerd in `resources/js/app.js`
- [ ] `resources/js/modules/` map aangemaakt
- [ ] Vite config werkend (`npm run dev` en `npm run build` slagen)

### Blade design system
- [ ] Layout component (`resources/views/components/layouts/app.blade.php`)
- [ ] Sidebar component met lege navigatie
- [ ] UI components: `card`, `button`, `badge`, `modal`, `empty-state`
- [ ] Form components: `input`, `select`, `textarea`, `error`
- [ ] Stats components: `stat-card`, `trend-badge`

### Home
- [ ] `DashboardController` aanmaken met alleen een `index()` methode
- [ ] Route `/` → `DashboardController@index` → `dashboard` naam
- [ ] View `resources/views/pages/dashboard/index.blade.php` met lege grid-structuur
- [ ] Dark mode actief via CSS custom properties

### Klaar om features te bouwen
Zodra bovenstaande checklist compleet is, gebruik de **"Volgorde bij het implementeren van een nieuwe feature"** in sectie 13 voor elke volgende module.
