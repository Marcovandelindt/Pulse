<x-layouts.app title="Import Nintendo sessions">
    <div class="page-header">
        <div class="page-header__left">
            <a href="{{ route('nintendo.index') }}" class="page-header__back">Nintendo Switch</a>
            <h1 class="page-header__title">Import via screenshot</h1>
        </div>
    </div>

    <div style="max-width:40rem;">

        @if(! $tesseractAvailable)
            <div class="alert alert--error mb-6">
                <strong>Tesseract is not installed.</strong>
                Run <code>winget install UB-Mannheim.TesseractOCR</code> in an admin terminal, then restart your terminal and this server.
            </div>
        @endif

        {{-- Instructions --}}
        <x-ui.card title="How it works" class="mb-6">
            <ol style="display:flex;flex-direction:column;gap:.75rem;padding-left:1.25rem;color:var(--color-text-muted);font-size:.875rem;line-height:1.6;">
                <li>Open the <strong style="color:var(--color-text-primary);">Nintendo Store app</strong> on your phone</li>
                <li>Go to your profile → <strong style="color:var(--color-text-primary);">Play Activity</strong></li>
                <li>Take screenshots of the sessions list (multiple pages are fine)</li>
                <li>Upload them below — Tesseract reads game names, dates and durations from each image</li>
                <li>Review and confirm what was extracted</li>
            </ol>
        </x-ui.card>

        {{-- Upload form --}}
        <x-ui.card title="Upload screenshots">
            <form
                method="POST"
                action="{{ route('nintendo.import.store') }}"
                enctype="multipart/form-data"
                x-data="{
                    files: [],
                    previews: [],
                    addFiles(list) {
                        for (const f of list) {
                            this.files.push(f);
                            this.previews.push(URL.createObjectURL(f));
                        }
                        this.syncInput();
                    },
                    removeFile(i) {
                        URL.revokeObjectURL(this.previews[i]);
                        this.files.splice(i, 1);
                        this.previews.splice(i, 1);
                        this.syncInput();
                    },
                    syncInput() {
                        const dt = new DataTransfer();
                        this.files.forEach(f => dt.items.add(f));
                        this.$refs.fileInput.files = dt.files;
                    },
                }"
            >
                @csrf

                <input
                    type="file"
                    name="screenshots[]"
                    accept="image/*"
                    multiple
                    x-ref="fileInput"
                    style="display:none;"
                    @change="addFiles($event.target.files)"
                >

                {{-- Drop zone --}}
                <div
                    style="border:2px dashed var(--color-border);border-radius:var(--radius-lg);padding:2rem;text-align:center;cursor:pointer;transition:border-color var(--transition-base);"
                    @dragover.prevent="$el.style.borderColor='var(--color-brand)'"
                    @dragleave="$el.style.borderColor='var(--color-border)'"
                    @drop.prevent="$el.style.borderColor='var(--color-border)'; addFiles($event.dataTransfer.files)"
                    @click="$refs.fileInput.click()"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:2.5rem;height:2.5rem;color:var(--color-text-muted);margin:0 auto .75rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    <p style="color:var(--color-text-muted);font-size:.875rem;" x-text="files.length > 0 ? `${files.length} screenshot${files.length > 1 ? 's' : ''} selected — click or drop to add more` : 'Click or drag your screenshots here'"></p>
                    <p style="color:var(--color-text-muted);font-size:.75rem;margin-top:.25rem;">PNG, JPG up to 10MB · max 10 screenshots</p>
                </div>

                {{-- Thumbnail grid --}}
                <template x-if="previews.length > 0">
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(7rem,1fr));gap:.75rem;margin-top:1rem;">
                        <template x-for="(src, i) in previews" :key="i">
                            <div style="position:relative;">
                                <img :src="src" style="width:100%;height:7rem;object-fit:cover;border-radius:var(--radius-md);display:block;">
                                <button
                                    type="button"
                                    @click.stop="removeFile(i)"
                                    style="position:absolute;top:.25rem;right:.25rem;background:rgba(0,0,0,.65);border:none;border-radius:50%;width:1.375rem;height:1.375rem;color:#fff;cursor:pointer;font-size:.875rem;line-height:1;display:flex;align-items:center;justify-content:center;"
                                >×</button>
                            </div>
                        </template>
                    </div>
                </template>

                @error('screenshots')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror
                @error('screenshots.*')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror

                <div style="margin-top:1.25rem;">
                    <button
                        type="submit"
                        class="btn btn--primary"
                        style="width:100%;"
                        :disabled="files.length === 0"
                    >
                        Extract sessions
                    </button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
