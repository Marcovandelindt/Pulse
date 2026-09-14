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
                <li>Take a screenshot of the sessions list</li>
                <li>Upload it below — Tesseract reads the game names, dates and durations</li>
                <li>Review and confirm what was extracted</li>
            </ol>
        </x-ui.card>

        {{-- Upload form --}}
        <x-ui.card title="Upload screenshot">
            <form
                method="POST"
                action="{{ route('nintendo.import.store') }}"
                enctype="multipart/form-data"
                x-data="{ file: null, preview: null }"
            >
                @csrf

                <div
                    style="border:2px dashed var(--color-border);border-radius:var(--radius-lg);padding:2rem;text-align:center;cursor:pointer;transition:border-color var(--transition-base);"
                    @dragover.prevent="$el.style.borderColor='var(--color-brand)'"
                    @dragleave="$el.style.borderColor='var(--color-border)'"
                    @drop.prevent="file=$event.dataTransfer.files[0]; preview=URL.createObjectURL(file); $refs.fileInput.files=$event.dataTransfer.files"
                    @click="$refs.fileInput.click()"
                >
                    <input
                        type="file"
                        name="screenshot"
                        accept="image/*"
                        x-ref="fileInput"
                        style="display:none;"
                        @change="file=$event.target.files[0]; preview=URL.createObjectURL(file)"
                        required
                    >

                    <template x-if="!preview">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:2.5rem;height:2.5rem;color:var(--color-text-muted);margin:0 auto .75rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                            </svg>
                            <p style="color:var(--color-text-muted);font-size:.875rem;">Click or drag your screenshot here</p>
                            <p style="color:var(--color-text-muted);font-size:.75rem;margin-top:.25rem;">PNG, JPG up to 10MB</p>
                        </div>
                    </template>

                    <template x-if="preview">
                        <div>
                            <img :src="preview" style="max-height:16rem;max-width:100%;border-radius:var(--radius-md);margin:0 auto;display:block;">
                            <p style="color:var(--color-text-muted);font-size:.75rem;margin-top:.75rem;" x-text="file.name"></p>
                        </div>
                    </template>
                </div>

                @error('screenshot')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror

                <div style="margin-top:1.25rem;">
                    <button
                        type="submit"
                        class="btn btn--primary"
                        style="width:100%;"
                        :disabled="!file"
                    >
                        Extract sessions
                    </button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
