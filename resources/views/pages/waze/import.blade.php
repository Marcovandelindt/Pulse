<x-layouts.app title="Import Waze Data">

    <x-layout.page-header title="Import Waze Data">
        <x-slot:actions>
            <a href="{{ route('waze.index') }}" class="btn btn--secondary btn--sm">&larr; Waze</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        <x-ui.card title="Upload archive">
            <form method="POST" action="{{ route('waze.import.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <p class="text-sm" style="color: var(--color-text-muted);">
                    Upload the <code>.zip</code> file from your Waze data export, or a single
                    <code>account_activity_*.csv</code> file. Duplicate drives are skipped automatically.
                </p>

                <div class="form-group">
                    <label class="form-label" for="file">Waze archive</label>
                    <input
                        type="file"
                        id="file"
                        name="file"
                        accept=".zip,.csv"
                        class="form-input"
                        style="padding: 0.375rem 0.5rem;"
                    >
                    <x-form.error name="file" />
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">ZIP password <span style="color: var(--color-text-muted); font-weight: 400;">(optional)</span></label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        autocomplete="off"
                    >
                    <x-form.error name="password" />
                </div>

                <button type="submit" class="btn btn--primary w-full">Import</button>
            </form>
        </x-ui.card>

        <x-ui.card title="How to export your Waze data">
            <ol class="space-y-2 text-sm" style="color: var(--color-text-muted); padding-left: 1.25rem; list-style: decimal;">
                <li>Open <strong style="color: var(--color-text-primary);">waze.com</strong> and log in.</li>
                <li>Go to your account settings.</li>
                <li>Click <strong style="color: var(--color-text-primary);">"Download your Waze data"</strong>.</li>
                <li>Wait for the email with the download link (can take a few hours).</li>
                <li>Download the <code>.zip</code> file and upload it here.</li>
            </ol>

            <p class="text-sm mt-4" style="color: var(--color-text-muted);">
                Waze keeps approximately 2 months of drive history. Import monthly to keep your data up to date.
            </p>
        </x-ui.card>

    </div>

    <x-layout.notification />

</x-layouts.app>
