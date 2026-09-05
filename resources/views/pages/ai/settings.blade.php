<x-layouts.app title="AI Settings">

    <x-layout.page-header title="AI Settings">
        <x-slot:actions>
            <a href="{{ route('ai.chat') }}" class="btn btn--secondary btn--sm">← Back to chat</a>
        </x-slot:actions>
    </x-layout.page-header>

    @if(session('success'))
        <div class="ai-settings-page__success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('ai.settings.update') }}" class="ai-settings-page">
        @csrf

        {{-- Behavior --}}
        <div class="ai-settings-page__section">
            <h2 class="ai-settings-page__section-title">Assistant behavior</h2>

            <div class="card">
                <div class="card__body">
                    <div class="ai-settings-page__field">
                        <label class="ai-settings-page__label" for="personality">Personality & tone</label>
                        <p class="ai-settings-page__hint">
                            Describe how the assistant should communicate. These instructions are added on top of the defaults.
                            Leave empty to use the default behavior.
                        </p>
                        <textarea
                            id="personality"
                            name="personality"
                            class="ai-settings-page__textarea"
                            rows="4"
                            placeholder="e.g. Praat altijd casual in het Nederlands. Houd antwoorden kort en bondig. Gebruik geen formele taal."
                        >{{ old('personality', $personality) }}</textarea>
                        @error('personality')
                            <p class="ai-settings-page__error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Model --}}
        <div class="ai-settings-page__section">
            <h2 class="ai-settings-page__section-title">Model</h2>

            <div class="card">
                <div class="card__body">
                    <div class="ai-settings-page__field">
                        <label class="ai-settings-page__label" for="model">Ollama model</label>
                        <p class="ai-settings-page__hint">
                            Override the model set in <code>.env</code>. Leave empty to use the default
                            <code>{{ $defaultModel }}</code>.
                            The model must be available in your local Ollama installation.
                        </p>
                        <input
                            id="model"
                            type="text"
                            name="model"
                            class="ai-settings-page__input"
                            placeholder="{{ $defaultModel }}"
                            value="{{ old('model', $model) }}"
                        >
                        @error('model')
                            <p class="ai-settings-page__error">{{ $message }}</p>
                        @enderror
                        <p class="ai-settings-page__hint" style="margin-top: 0.5rem;">
                            Popular models: <code>llama3.1:8b</code> · <code>qwen2.5:7b</code> · <code>llama3.2:3b</code> (faster, less capable)
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="ai-settings-page__footer">
            <button type="submit" class="btn btn--primary">Save settings</button>
        </div>

    </form>

</x-layouts.app>
