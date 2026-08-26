<x-layouts.app :title="$pattern->title">

    <x-layout.page-header :title="$pattern->title">
        <x-slot:actions>
            <a href="{{ route('patterns.index') }}" class="btn btn--secondary btn--sm">&larr; Patterns</a>
            <a href="{{ route('patterns.edit', $pattern) }}" class="btn btn--secondary btn--sm">Edit</a>
            <form method="POST" action="{{ route('patterns.destroy', $pattern) }}"
                  onsubmit="return confirm('Delete this pattern? Insights will not be deleted.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--danger btn--sm">Delete</button>
            </form>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="pattern-detail">

        @if ($pattern->description)
            <div class="pattern-detail__description">{{ $pattern->description }}</div>
        @endif

        <div class="pattern-detail__section">
            <h3 class="pattern-detail__section-title">
                Insights
                <span class="pattern-detail__count">{{ $pattern->insights->count() }}</span>
            </h3>

            @if ($pattern->insights->isNotEmpty())
                <div class="insights-list">
                    @foreach ($pattern->insights->sortByDesc('created_at') as $insight)
                        <a href="{{ route('insights.show', $insight) }}" class="insight-card">
                            <div class="insight-card__header">
                                <span class="insight-card__title">{{ $insight->title }}</span>
                                <span class="insight-card__date">{{ $insight->created_at->format('d M Y') }}</span>
                            </div>
                            @if ($insight->summary)
                                <p class="insight-card__summary">{{ $insight->summary }}</p>
                            @elseif ($insight->content)
                                <p class="insight-card__summary">{{ Str::limit($insight->content, 120) }}</p>
                            @endif
                            @if (!empty($insight->tags) || $insight->category)
                                <div class="insight-card__meta">
                                    @if ($insight->category)
                                        <span class="badge badge--muted">{{ $insight->category }}</span>
                                    @endif
                                    @foreach (array_slice($insight->tags ?? [], 0, 3) as $tag)
                                        <span class="badge badge--subtle">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @else
                <p class="pattern-detail__empty">No insights linked to this pattern yet.</p>
            @endif
        </div>

    </div>

    <x-layout.notification />

</x-layouts.app>
