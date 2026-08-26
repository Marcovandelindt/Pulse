<x-layouts.app :title="$insight->title">

    <x-layout.page-header :title="$insight->title">
        <x-slot:actions>
            <form method="POST" action="{{ route('insights.pin', $insight) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn--secondary btn--sm" title="{{ $insight->is_pinned ? 'Unpin' : 'Pin' }}">
                    {{ $insight->is_pinned ? 'Unpin' : 'Pin' }}
                </button>
            </form>
            <a href="{{ route('insights.edit', $insight) }}" class="btn btn--secondary btn--sm">Edit</a>
            <form method="POST" action="{{ route('insights.destroy', $insight) }}"
                  onsubmit="return confirm('Delete this insight? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--danger btn--sm">Delete</button>
            </form>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="insight-detail">

        {{-- Main content --}}
        <div class="insight-detail__main">

            <div class="insight-detail__content-card">
                @if ($insight->summary)
                    <p class="insight-detail__summary">{{ $insight->summary }}</p>
                    <hr class="insight-detail__divider">
                @endif
                <div class="insight-detail__body ql-editor">{!! $insight->content !!}</div>
            </div>

            {{-- Patterns --}}
            <div class="insight-detail__section">
                <div class="insight-detail__section-header">
                    <h3 class="insight-detail__section-title">Patterns</h3>
                    <a href="{{ route('patterns.index') }}" class="insight-detail__section-link">Manage patterns</a>
                </div>

                @if ($insight->patterns->isNotEmpty())
                    <div class="insight-linked-list">
                        @foreach ($insight->patterns as $pattern)
                            <div class="insight-linked-item">
                                <a href="{{ route('patterns.show', $pattern) }}" class="insight-linked-item__label">
                                    {{ $pattern->title }}
                                </a>
                                <form method="POST" action="{{ route('insights.patterns.destroy', [$insight, $pattern]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--danger btn--sm">Unlink</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($availablePatterns->isNotEmpty())
                    <form method="POST" action="{{ route('insights.patterns.store', $insight) }}" class="insight-linked-add">
                        @csrf
                        <select name="pattern" class="form-select" required>
                            <option value="">— Link a pattern —</option>
                            @foreach ($availablePatterns as $pattern)
                                <option value="{{ $pattern->id }}">{{ $pattern->title }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn--secondary btn--sm">Link</button>
                    </form>
                @elseif ($insight->patterns->isEmpty())
                    <p class="insight-detail__empty-note">No patterns yet. <a href="{{ route('patterns.index') }}" style="color: var(--color-brand-light);">Create one</a></p>
                @endif
            </div>

            {{-- Related insights --}}
            <div class="insight-detail__section">
                <h3 class="insight-detail__section-title">Related insights</h3>

                @if ($allRelated->isNotEmpty())
                    <div class="insight-linked-list">
                        @foreach ($allRelated as $related)
                            <div class="insight-linked-item">
                                <a href="{{ route('insights.show', $related) }}" class="insight-linked-item__label">
                                    {{ $related->title }}
                                </a>
                                <form method="POST" action="{{ route('insights.related.destroy', [$insight, $related]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--danger btn--sm">Unlink</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($availableInsights->isNotEmpty())
                    <form method="POST" action="{{ route('insights.related.store', $insight) }}" class="insight-linked-add">
                        @csrf
                        <select name="related" class="form-select" required>
                            <option value="">— Link a related insight —</option>
                            @foreach ($availableInsights as $other)
                                <option value="{{ $other->id }}">{{ $other->title }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn--secondary btn--sm">Link</button>
                    </form>
                @endif
            </div>

        </div>

        {{-- Sidebar: meta --}}
        <div class="insight-detail__sidebar">

            <div class="insight-detail__meta-card">
                <div class="insight-detail__meta-row">
                    <span class="insight-detail__meta-label">Created</span>
                    <span class="insight-detail__meta-value">{{ $insight->created_at->format('d M Y') }}</span>
                </div>
                @if ($insight->updated_at->ne($insight->created_at))
                    <div class="insight-detail__meta-row">
                        <span class="insight-detail__meta-label">Updated</span>
                        <span class="insight-detail__meta-value">{{ $insight->updated_at->format('d M Y') }}</span>
                    </div>
                @endif
                @if ($insight->category)
                    <div class="insight-detail__meta-row">
                        <span class="insight-detail__meta-label">Category</span>
                        <span class="insight-detail__meta-value">{{ $insight->category }}</span>
                    </div>
                @endif
                <div class="insight-detail__meta-row">
                    <span class="insight-detail__meta-label">Pinned</span>
                    <span class="insight-detail__meta-value">{{ $insight->is_pinned ? 'Yes' : 'No' }}</span>
                </div>
                <div class="insight-detail__meta-row">
                    <span class="insight-detail__meta-label">Quick ref</span>
                    <span class="insight-detail__meta-value">{{ $insight->is_quick_ref ? 'Yes' : 'No' }}</span>
                </div>
            </div>

            @if (!empty($insight->tags))
                <div class="insight-detail__tags-card">
                    <div class="insight-detail__tags-label">Tags</div>
                    <div class="insight-detail__tags">
                        @foreach ($insight->tags as $tag)
                            <span class="badge badge--subtle">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

    </div>

    <x-layout.notification />

</x-layouts.app>
