<x-layouts.app :title="$journalEntry->title ?? $journalEntry->date->format('F j, Y')">

<x-layout.page-header :title="$journalEntry->title ?? $journalEntry->date->format('l, F j, Y')">
    <x-slot:actions>
        <a href="{{ route('journal.edit', $journalEntry) }}" class="btn btn--secondary btn--sm">Edit</a>
        <a href="{{ route('journal.index') }}" class="btn btn--secondary btn--sm">← Journal</a>
    </x-slot:actions>
</x-layout.page-header>

<div class="grid gap-6 lg:[grid-template-columns:70%_30%]">

    <div>
        <x-ui.card>
            <div class="journal-entry__meta">
                <span class="journal-entry__date">{{ $journalEntry->date->format('l, F j, Y') }}</span>
                @if($journalEntry->mood)
                    <span class="journal-entry__mood">
                        {{ $journalEntry->mood->emoji() }} {{ $journalEntry->mood->label() }}
                    </span>
                @endif
                @if($journalEntry->energy_level)
                    <span class="journal-entry__energy">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="journal-entry__energy-dot {{ $i <= $journalEntry->energy_level ? 'journal-entry__energy-dot--filled' : '' }}"></span>
                        @endfor
                    </span>
                @endif
            </div>

            <div class="journal-entry__body">
                {!! nl2br(e($journalEntry->content)) !!}
            </div>

            @if($journalEntry->tags)
                <div class="journal-entry__tags">
                    @foreach($journalEntry->tags as $tag)
                        <span class="journal-list__tag">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>

    <div class="space-y-4">
        <x-ui.card title="Navigate">
            <div class="space-y-2">
                @if($next)
                    <a href="{{ route('journal.show', $next) }}" class="journal-nav__link">
                        <span class="text-xs" style="color:var(--color-text-muted)">Newer</span>
                        <span>{{ $next->date->format('M j, Y') }}</span>
                    </a>
                @endif
                @if($prev)
                    <a href="{{ route('journal.show', $prev) }}" class="journal-nav__link">
                        <span class="text-xs" style="color:var(--color-text-muted)">Older</span>
                        <span>{{ $prev->date->format('M j, Y') }}</span>
                    </a>
                @endif
                @if(!$prev && !$next)
                    <p class="text-sm" style="color:var(--color-text-muted)">This is the only entry.</p>
                @endif
            </div>
        </x-ui.card>

        <div x-data="{ confirm: false }">
            <template x-if="!confirm">
                <button @click="confirm = true" class="btn btn--danger btn--sm w-full justify-center">Delete entry</button>
            </template>
            <template x-if="confirm">
                <div class="space-y-2">
                    <p class="text-sm" style="color:var(--color-text-muted)">Are you sure?</p>
                    <form method="POST" action="{{ route('journal.destroy', $journalEntry) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn--danger btn--sm w-full justify-center">Yes, delete</button>
                    </form>
                    <button @click="confirm = false" class="btn btn--secondary btn--sm w-full justify-center">Cancel</button>
                </div>
            </template>
        </div>
    </div>

</div>

</x-layouts.app>
