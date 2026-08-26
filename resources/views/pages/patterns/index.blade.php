<x-layouts.app title="Patterns">

    <x-layout.page-header title="Patterns">
        <x-slot:actions>
            <a href="{{ route('insights.index') }}" class="btn btn--secondary btn--sm">&larr; Insights</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="patterns-layout">

        {{-- Pattern list --}}
        <div class="patterns-list-wrap">
            @if ($patterns->isEmpty())
                <x-ui.empty-state
                    title="No patterns yet"
                    description="Patterns group insights that share a common theme."
                />
            @else
                <div class="patterns-list">
                    @foreach ($patterns as $pattern)
                        <a href="{{ route('patterns.show', $pattern) }}" class="pattern-card">
                            <div class="pattern-card__title">{{ $pattern->title }}</div>
                            @if ($pattern->description)
                                <p class="pattern-card__description">{{ Str::limit($pattern->description, 100) }}</p>
                            @endif
                            <div class="pattern-card__meta">
                                <span class="badge badge--muted">{{ $pattern->insights_count }} {{ Str::plural('insight', $pattern->insights_count) }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Create form --}}
        <div class="patterns-create-wrap">
            <div class="patterns-create-card">
                <h3 class="patterns-create-card__title">New pattern</h3>
                <form method="POST" action="{{ route('patterns.store') }}" class="flex flex-col gap-4">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="pattern-title">Title</label>
                        <input
                            type="text"
                            id="pattern-title"
                            name="title"
                            value="{{ old('title') }}"
                            class="form-input"
                            placeholder="Name this pattern..."
                            required
                        >
                        <x-form.error name="title" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pattern-desc">Description <span class="form-label__optional">optional</span></label>
                        <textarea
                            id="pattern-desc"
                            name="description"
                            class="form-textarea"
                            rows="3"
                            placeholder="Describe the recurring pattern..."
                        >{{ old('description') }}</textarea>
                        <x-form.error name="description" />
                    </div>
                    <button type="submit" class="btn btn--primary">Create pattern</button>
                </form>
            </div>
        </div>

    </div>

    <x-layout.notification />

</x-layouts.app>
