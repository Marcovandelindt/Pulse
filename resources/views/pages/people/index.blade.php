<x-layouts.app title="People">

    <x-layout.page-header title="People">
        <x-slot:actions>
            <a href="{{ route('movies.index') }}" class="btn btn--secondary btn--sm">&larr; Movies</a>
            <a href="{{ route('tv.index') }}" class="btn btn--secondary btn--sm">&larr; TV Series</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="people-index">
        @foreach ($people as $i => $person)
            <a href="{{ route('people.show', $person) }}" class="people-index__card">
                <span class="people-index__rank">#{{ ($people->currentPage() - 1) * $people->perPage() + $loop->iteration }}</span>
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
                    <div class="people-index__counts">
                        @if ($person->movies_count > 0)
                            <span class="badge badge--muted">{{ $person->movies_count }} {{ Str::plural('movie', $person->movies_count) }}</span>
                        @endif
                        @if ($person->tv_series_count > 0)
                            <span class="badge badge--muted">{{ $person->tv_series_count }} {{ Str::plural('series', $person->tv_series_count) }}</span>
                        @endif
                    </div>
                </div>
                @php
                    $mins = (int) $person->watch_minutes;
                    $h = intdiv($mins, 60);
                    $m = $mins % 60;
                    $watchTime = $h > 0 ? ($m > 0 ? "{$h}h {$m}m" : "{$h}h") : ($m > 0 ? "{$m}m" : '—');
                @endphp
                <div class="people-index__total" title="{{ $mins }} minutes">{{ $watchTime }}</div>
            </a>
        @endforeach
    </div>

    @if ($people->hasPages())
        <div class="pagination-wrap">
            {{ $people->links() }}
        </div>
    @endif

</x-layouts.app>
