<x-layouts.app title="Journal">

<x-layout.page-header title="Journal">
    <x-slot:actions>
        <a href="{{ route('journal.create') }}" class="btn btn--primary btn--sm">+ New entry</a>
    </x-slot:actions>
</x-layout.page-header>

<div class="grid grid-cols-3 gap-4 mb-6">
    <x-stats.stat-card label="Total entries" :value="$total" icon="book-open" />
    <x-stats.stat-card label="Current streak" :value="$streak . ($streak === 1 ? ' day' : ' days')" icon="fire" />
    <x-stats.stat-card label="This month" :value="$entries->where('date', '>=', now()->startOfMonth())->count()" icon="calendar" />
</div>

@if($entries->isEmpty())
    <x-ui.empty-state title="No entries yet" description="Start writing your first journal entry." />
@else
    <x-ui.card>
        <div class="journal-list">
            @foreach($entries as $entry)
                <a href="{{ route('journal.show', $entry) }}" class="journal-list__item">
                    <div class="journal-list__date">
                        <span class="journal-list__day">{{ $entry->date->format('d') }}</span>
                        <span class="journal-list__month">{{ $entry->date->format('M') }}</span>
                    </div>
                    <div class="journal-list__content">
                        <div class="journal-list__title">
                            {{ $entry->title ?? $entry->date->format('l, F j') }}
                            @if($entry->mood)
                                <span class="journal-list__mood">{{ $entry->mood->emoji() }}</span>
                            @endif
                        </div>
                        <div class="journal-list__preview">{{ Str::limit($entry->content, 120) }}</div>
                        @if($entry->tags)
                            <div class="journal-list__tags">
                                @foreach($entry->tags as $tag)
                                    <span class="journal-list__tag">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if($entry->energy_level)
                        <div class="journal-list__energy">
                            @for($i = 1; $i <= 5; $i++)
                                <span class="journal-list__energy-dot {{ $i <= $entry->energy_level ? 'journal-list__energy-dot--filled' : '' }}"></span>
                            @endfor
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
    </x-ui.card>

    <div class="mt-4">
        {{ $entries->links() }}
    </div>
@endif

</x-layouts.app>
