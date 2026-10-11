<x-layouts.app title="Sleep Music">

<x-layout.page-header title="Sleep Music">
    <x-slot:actions>
        <a href="{{ route('playstation.index') }}" class="btn btn--secondary btn--sm">← PlayStation</a>
    </x-slot:actions>
</x-layout.page-header>

@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
         style="background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25);">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    <div class="lg:col-span-2">
        <x-ui.card title="Tracks">
            @if($tracks->isEmpty())
                <x-ui.empty-state title="No tracks yet" description="Upload an audio file to get started." />
            @else
                <div class="sleep-music-list">
                    @foreach($tracks as $track)
                        <div class="sleep-music-item">
                            <div class="sleep-music-item__icon">♪</div>
                            <div class="sleep-music-item__info">
                                <div class="sleep-music-item__title">{{ $track->displayTitle() }}</div>
                                @if($track->game_name)
                                    <div class="sleep-music-item__path">{{ $track->game_name }}</div>
                                @endif
                            </div>
                            <audio controls preload="none" class="sleep-music-item__player">
                                <source src="{{ Storage::url($track->path) }}">
                            </audio>
                            <form method="POST" action="{{ route('playstation.sleep-music.destroy', $track) }}"
                                  onsubmit="return confirm('Remove this track?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--danger btn--sm btn--icon" title="Remove">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>

    <div>
        <x-ui.card title="Upload track">
            <form method="POST" action="{{ route('playstation.sleep-music.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-field mb-4">
                    <label class="form-label" for="audio">Audio file</label>
                    <input type="file" id="audio" name="audio" accept=".mp3,.ogg,.wav,.m4a,.flac"
                           class="form-input" required>
                    @error('audio')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    <p class="text-xs mt-1" style="color: var(--color-text-muted);">MP3, OGG, WAV, M4A or FLAC — max 50 MB</p>
                </div>
                <div class="form-field mb-4">
                    <label class="form-label" for="title">Title <span style="color: var(--color-text-muted); font-weight: 400;">(optional)</span></label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}"
                           placeholder="Leave blank to use filename"
                           class="form-input" maxlength="255">
                    @error('title')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-field mb-4">
                    <label class="form-label" for="game_name">Game <span style="color: var(--color-text-muted); font-weight: 400;">(optional)</span></label>
                    <input type="text" id="game_name" name="game_name" value="{{ old('game_name') }}"
                           placeholder="e.g. Final Fantasy XVI"
                           class="form-input" maxlength="255">
                    @error('game_name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn btn--primary w-full justify-center">Upload</button>
            </form>
        </x-ui.card>
    </div>

</div>

</x-layouts.app>
