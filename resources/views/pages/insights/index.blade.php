<x-layouts.app title="Insights">

    <x-layout.page-header title="Insights">
        <x-slot:actions>
            <a href="{{ route('patterns.index') }}" class="btn btn--secondary btn--sm">Patterns</a>
            <a href="{{ route('insights.create') }}" class="btn btn--primary btn--sm">+ New insight</a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Stats --}}
    <div class="stats-row">
        <x-stats.stat-card label="Total" :value="$totalCount" />
        <x-stats.stat-card label="Pinned" :value="$pinnedCount" />
        <x-stats.stat-card label="Quick reference" :value="$quickRefCount" />
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('insights.index') }}" class="insights-search">
        <div class="insights-search__field">
            <svg xmlns="http://www.w3.org/2000/svg" class="insights-search__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
            </svg>
            <input
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="Search insights..."
                class="insights-search__input"
                autofocus="{{ $search !== '' ? 'autofocus' : false }}"
            >
            @if ($search !== '')
                <a href="{{ route('insights.index') }}" class="insights-search__clear" title="Clear">&times;</a>
            @endif
        </div>
        <button type="submit" class="btn btn--secondary btn--sm">Search</button>
    </form>

    @if ($search !== '')
        <p class="insights-search__meta">
            {{ $insights->total() }} {{ Str::plural('result', $insights->total()) }} for <em>"{{ $search }}"</em>
        </p>
    @endif

    {{-- Pinned section --}}
    @if ($pinned->isNotEmpty() && $search === '')
        <div class="insights-section">
            <h2 class="insights-section__title">Pinned</h2>
            <div class="insights-list">
                @foreach ($pinned as $insight)
                    <a href="{{ route('insights.show', $insight) }}" class="insight-card insight-card--pinned">
                        <div class="insight-card__header">
                            <span class="insight-card__title">{{ $insight->title }}</span>
                            <span class="insight-card__date">{{ $insight->created_at->format('d M Y') }}</span>
                        </div>
                        @if ($insight->summary)
                            <p class="insight-card__summary">{{ $insight->summary }}</p>
                        @elseif ($insight->content)
                            <p class="insight-card__summary">{{ Str::limit($insight->content, 120) }}</p>
                        @endif
                        <div class="insight-card__meta">
                            @if ($insight->category)
                                <span class="badge badge--muted">{{ $insight->category }}</span>
                            @endif
                            @foreach (array_slice($insight->tags ?? [], 0, 3) as $tag)
                                <span class="badge badge--subtle">{{ $tag }}</span>
                            @endforeach
                            @if (count($insight->tags ?? []) > 3)
                                <span class="badge badge--muted">+{{ count($insight->tags) - 3 }}</span>
                            @endif
                            @if ($insight->patterns->isNotEmpty())
                                <span class="insight-card__pattern-count">{{ $insight->patterns->count() }} {{ Str::plural('pattern', $insight->patterns->count()) }}</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- All insights --}}
    @if ($pinned->isNotEmpty() && $search === '' && $insights->isNotEmpty())
        <h2 class="insights-section__title insights-section__title--spaced">Recent</h2>
    @endif

    @if ($insights->isNotEmpty())
        <div class="insights-list">
            @foreach ($insights as $insight)
                <a href="{{ route('insights.show', $insight) }}" class="insight-card">
                    <div class="insight-card__header">
                        <span class="insight-card__title">
                            @if ($insight->is_pinned)
                                <span class="insight-card__pin" title="Pinned">&#x1F4CC;</span>
                            @endif
                            {{ $insight->title }}
                        </span>
                        <span class="insight-card__date">{{ $insight->created_at->format('d M Y') }}</span>
                    </div>
                    @if ($insight->summary)
                        <p class="insight-card__summary">{{ $insight->summary }}</p>
                    @elseif ($insight->content)
                        <p class="insight-card__summary">{{ Str::limit($insight->content, 120) }}</p>
                    @endif
                    <div class="insight-card__meta">
                        @if ($insight->category)
                            <span class="badge badge--muted">{{ $insight->category }}</span>
                        @endif
                        @foreach (array_slice($insight->tags ?? [], 0, 3) as $tag)
                            <span class="badge badge--subtle">{{ $tag }}</span>
                        @endforeach
                        @if (count($insight->tags ?? []) > 3)
                            <span class="badge badge--muted">+{{ count($insight->tags) - 3 }}</span>
                        @endif
                        @if ($insight->patterns->isNotEmpty())
                            <span class="insight-card__pattern-count">{{ $insight->patterns->count() }} {{ Str::plural('pattern', $insight->patterns->count()) }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        @if ($insights->hasPages())
            <div class="pagination-wrap">
                {{ $insights->links() }}
            </div>
        @endif
    @elseif ($search === '' && $pinned->isEmpty())
        <x-ui.empty-state
            title="No insights yet"
            description="Start capturing what you discover about yourself."
        >
            <x-slot:action>
                <a href="{{ route('insights.create') }}" class="btn btn--primary">+ New insight</a>
            </x-slot:action>
        </x-ui.empty-state>
    @elseif ($search !== '')
        <x-ui.empty-state
            title="No results"
            description="No insights match your search."
        />
    @endif

    <x-layout.notification />

</x-layouts.app>
