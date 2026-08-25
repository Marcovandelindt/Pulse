<x-layouts.app title="People">

    <x-layout.page-header title="People">
        <x-slot:actions>
            <a href="{{ route('movies.index') }}" class="btn btn--secondary btn--sm">&larr; Movies</a>
            <a href="{{ route('tv.index') }}" class="btn btn--secondary btn--sm">&larr; TV Series</a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Search bar --}}
    <form method="GET" action="{{ route('actors.index') }}" class="people-search">
        <div class="people-search__field">
            <svg xmlns="http://www.w3.org/2000/svg" class="people-search__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
            </svg>
            <input
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="Search by actor name or character…"
                class="people-search__input"
                autofocus="{{ $search !== '' ? 'autofocus' : false }}"
            >
            @if ($search !== '')
                <a href="{{ route('actors.index') }}" class="people-search__clear" title="Clear search">&times;</a>
            @endif
        </div>
        <button type="submit" class="btn btn--primary btn--sm">Search</button>
    </form>

    @if ($search !== '')
        <p class="people-search__meta">
            {{ $people->total() }} {{ Str::plural('result', $people->total()) }} for <em>"{{ $search }}"</em>
        </p>
    @endif

    <div class="people-index">
        @forelse ($people as $person)
            @php
                $characters = collect();
                if ($search !== '') {
                    $movieChars = $person->movies
                        ->pluck('pivot.character')
                        ->filter()
                        ->unique();
                    $tvChars = $person->tvSeries
                        ->pluck('pivot.character')
                        ->filter()
                        ->unique();
                    $characters = $movieChars->merge($tvChars)->values();
                }
            @endphp
            <a href="{{ route('actors.show', $person) }}" class="people-index__card" data-searchable="{{ strtolower(($person->name_en ?? $person->name) . ' ' . $person->name) }}" data-label="{{ $person->name_en ?? $person->name }}" data-url="{{ route('actors.show', $person) }}">
                @if ($search === '')
                    <span class="people-index__rank">#{{ ($people->currentPage() - 1) * $people->perPage() + $loop->iteration }}</span>
                @endif
                <img
                    src="{{ $person->profile_url ?? asset('cast-placeholder.svg') }}"
                    alt="{{ $person->name }}"
                    class="people-index__photo"
                    loading="lazy"
                >
                <div class="people-index__info">
                    <div class="people-index__name">{{ $person->name_en ?? $person->name }}</div>
                    @if ($person->name_en && $person->name_en !== $person->name)
                        <div class="people-index__native">{{ $person->name }}</div>
                    @endif
                    @if ($characters->isNotEmpty())
                        <div class="people-index__characters">
                            {{ $characters->take(4)->join(', ') }}@if($characters->count() > 4) <span class="people-index__more">+{{ $characters->count() - 4 }} more</span>@endif
                        </div>
                    @endif
                    <div class="people-index__counts">
                        @if ($person->movies_count > 0)
                            <span class="badge badge--muted">{{ $person->movies_count }} {{ Str::plural('movie', $person->movies_count) }}</span>
                        @endif
                        @if ($person->tv_series_count > 0)
                            <span class="badge badge--muted">{{ $person->tv_series_count }} {{ Str::plural('series', $person->tv_series_count) }}</span>
                        @endif
                    </div>
                </div>
                @if ($search === '')
                    @php
                        $mins = (int) $person->watch_minutes;
                        $h = intdiv($mins, 60);
                        $m = $mins % 60;
                        $watchTime = $h > 0 ? ($m > 0 ? "{$h}h {$m}m" : "{$h}h") : ($m > 0 ? "{$m}m" : '—');
                    @endphp
                    <div class="people-index__total" title="{{ $mins }} minutes">{{ $watchTime }}</div>
                @endif
            </a>
        @empty
            <p class="people-search__empty">No actors or characters found for <em>"{{ $search }}"</em>.</p>
        @endforelse
    </div>

    @if ($people->hasPages())
        <div class="pagination-wrap">
            {{ $people->links() }}
        </div>
    @endif

</x-layouts.app>
