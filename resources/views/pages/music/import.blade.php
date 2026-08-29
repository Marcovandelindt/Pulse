<x-layouts.app title="Import Spotify History">

    <x-layout.page-header title="Import Spotify History">
        <x-slot:actions>
            <a href="{{ route('music.index') }}" class="btn btn--secondary btn--sm">&larr; Music</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <div class="lg:col-span-1">
            <x-ui.card title="Upload files">
                <form method="POST" action="{{ route('music.import.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <p class="text-sm" style="color: var(--color-text-muted);">
                        Upload one or more <code>Streaming_History_Audio_*.json</code> files from your Spotify data export.
                        Plays shorter than 30 seconds are skipped automatically.
                    </p>

                    <div class="form-group">
                        <label class="form-label" for="files">JSON files</label>
                        <input
                            type="file"
                            id="files"
                            name="files[]"
                            accept=".json,application/json"
                            multiple
                            class="form-input"
                            style="padding: 0.375rem 0.5rem;"
                        >
                        <x-form.error name="files" />
                        <x-form.error name="files.*" />
                    </div>

                    <button type="submit" class="btn btn--primary w-full">Start import</button>
                </form>
            </x-ui.card>
        </div>

        <div class="lg:col-span-2">
            <x-ui.card title="Import history">
                @if($imports->isEmpty())
                    <x-ui.empty-state title="No imports yet" description="Upload your Spotify history files to get started." />
                @else
                    <div class="import-list">
                        @foreach($imports as $import)
                            <div
                                class="import-item"
                                x-data="{
                                    status: '{{ $import->status }}',
                                    percentage: {{ $import->progress_percentage }},
                                    synced: {{ $import->synced }},
                                    skipped: {{ $import->skipped }},
                                    total: {{ $import->total_entries }},
                                    processed: {{ $import->processed }},
                                    error: @js($import->error),
                                    init() {
                                        if (['pending', 'processing'].includes(this.status)) {
                                            this.poll();
                                        }
                                    },
                                    async poll() {
                                        try {
                                            const res  = await fetch('{{ route('music.import.progress', $import) }}');
                                            const data = await res.json();
                                            this.status     = data.status;
                                            this.percentage = data.percentage;
                                            this.synced     = data.synced;
                                            this.skipped    = data.skipped;
                                            this.total      = data.total_entries;
                                            this.processed  = data.processed;
                                            this.error      = data.error;
                                        } catch {}
                                        if (['pending', 'processing'].includes(this.status)) {
                                            setTimeout(() => this.poll(), 2000);
                                        }
                                    },
                                }"
                            >
                                <div class="import-item__header">
                                    <div class="import-item__filename">{{ $import->filename }}</div>
                                    <div class="import-item__meta">
                                        <span class="text-xs" style="color: var(--color-text-muted);">{{ $import->created_at->diffForHumans() }}</span>
                                        <span
                                            class="badge import-item__badge"
                                            :class="{
                                                'import-item__badge--pending':    status === 'pending',
                                                'import-item__badge--processing': status === 'processing',
                                                'import-item__badge--done':       status === 'done',
                                                'import-item__badge--failed':     status === 'failed',
                                            }"
                                            x-text="status"
                                        ></span>
                                    </div>
                                </div>

                                <div class="import-item__progress-wrap" x-show="status === 'processing' || (status === 'done' && total > 0)">
                                    <div class="import-item__progress">
                                        <div class="import-item__progress-bar" :style="`width: ${percentage}%`"></div>
                                    </div>
                                    <span class="import-item__pct" x-text="`${percentage}%`"></span>
                                </div>

                                <div class="import-item__stats" x-show="status !== 'pending'">
                                    <span x-show="total > 0" x-text="`${total.toLocaleString()} entries`" style="color: var(--color-text-muted);"></span>
                                    <span x-show="synced > 0" x-text="`${synced.toLocaleString()} synced`" style="color: #4ade80;"></span>
                                    <span x-show="skipped > 0" x-text="`${skipped.toLocaleString()} skipped`" style="color: var(--color-text-muted);"></span>
                                    <span x-show="error" x-text="error" style="color: #f87171;"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>

    </div>

    <x-layout.notification />

</x-layouts.app>
