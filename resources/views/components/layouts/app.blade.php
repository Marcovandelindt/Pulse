@props(['title' => 'Pulse'])

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — Pulse</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
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

    {{-- Global search overlay --}}
    <div
        x-data="globalSearch"
        x-show="open"
        x-transition:enter="gs-enter"
        x-transition:enter-start="gs-enter-start"
        x-transition:enter-end="gs-enter-end"
        x-transition:leave="gs-leave"
        x-transition:leave-start="gs-leave-start"
        x-transition:leave-end="gs-leave-end"
        class="global-search"
        style="display:none;"
    >
        <div class="global-search__backdrop" @click="close()"></div>
        <div class="global-search__panel">
            <div class="global-search__input-row">
                <svg class="global-search__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
                <input
                    x-ref="input"
                    x-model="query"
                    @input="onInput()"
                    @keydown="onKeydown($event)"
                    type="search"
                    :placeholder="pageMode ? 'Zoek op deze pagina…' : 'Zoek films en series…'"
                    class="global-search__input"
                    autocomplete="off"
                >
                <span x-show="loading" class="global-search__spinner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="animate-spin" fill="none" viewBox="0 0 24 24" style="width:1rem;height:1rem;">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60" stroke-dashoffset="20" stroke-linecap="round"/>
                    </svg>
                </span>
                <kbd class="global-search__esc" @click="close()">Esc</kbd>
            </div>

            {{-- Global mode: results list --}}
            <template x-if="!pageMode">
                <div>
                    <div x-show="results.length > 0" class="global-search__results">
                        <template x-for="(result, i) in results" :key="result.type + result.id">
                            <a
                                :href="result.url"
                                class="global-search__result"
                                :class="{ 'global-search__result--active': i === selectedIndex }"
                                @click="close()"
                                @mouseenter="selectedIndex = i"
                            >
                                <img :src="result.poster_url" :alt="result.title" class="global-search__poster">
                                <div class="global-search__result-info">
                                    <div class="global-search__result-title" x-text="result.title"></div>
                                    <div class="global-search__result-sub" x-text="result.subtitle"></div>
                                </div>
                                <span class="global-search__badge" :class="result.type === 'movie' ? 'global-search__badge--movie' : 'global-search__badge--tv'" x-text="result.type === 'movie' ? 'Film' : 'Serie'"></span>
                            </a>
                        </template>
                    </div>
                    <div x-show="query.length >= 2 && !loading && results.length === 0" class="global-search__empty">
                        Geen resultaten voor "<span x-text="query" class="text-[var(--color-text-primary)]"></span>"
                    </div>
                    <div class="global-search__footer">
                        <span><kbd>↑</kbd><kbd>↓</kbd> navigeren</span>
                        <span><kbd>↵</kbd> openen</span>
                        <span><kbd>Ctrl F</kbd> openen</span>
                    </div>
                </div>
            </template>

            {{-- Page mode: results list + counter --}}
            <template x-if="pageMode">
                <div>
                    <div x-show="pageResults.length > 0" class="global-search__results">
                        <template x-for="(result, i) in pageResults" :key="result.url">
                            <a
                                :href="result.url"
                                class="global-search__result"
                                :class="{ 'global-search__result--active': i === selectedIndex }"
                                @click="close()"
                                @mouseenter="selectedIndex = i"
                            >
                                <img x-show="result.img" :src="result.img" :alt="result.label" class="global-search__poster">
                                <div x-show="!result.img" class="global-search__poster global-search__poster--empty"></div>
                                <div class="global-search__result-info">
                                    <div class="global-search__result-title" x-text="result.label"></div>
                                </div>
                            </a>
                        </template>
                    </div>

                    <div class="global-search__page-status">
                        <template x-if="query.length === 0">
                            <span class="global-search__page-hint">Typ om te filteren…</span>
                        </template>
                        <template x-if="query.length > 0 && pageResultCount > 0">
                            <span class="global-search__page-count" x-text="`${pageResultCount} van ${pageTotalCount} resultaten`"></span>
                        </template>
                        <template x-if="query.length > 0 && pageResultCount === 0">
                            <span class="global-search__page-empty">Geen resultaten voor "<span x-text="query"></span>"</span>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div
        x-data="{ toasts: [] }"
        @toast.window="toasts.push({ message: $event.detail.message, type: $event.detail.type ?? 'success', id: Date.now() })"
        class="toast-container"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                class="toast"
                :class="toast.type === 'success' ? 'toast--success' : 'toast--error'"
                x-show="true"
                x-transition
                x-init="setTimeout(() => { toasts = toasts.filter(t => t.id !== toast.id) }, 3000)"
            >
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

</body>
</html>
