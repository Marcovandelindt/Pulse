<x-layouts.app title="Steam Settings">

<x-layout.page-header title="Steam Settings">
    <x-slot:actions>
        <a href="{{ route('steam.index') }}" class="btn btn--secondary btn--sm">← Back</a>
    </x-slot:actions>
</x-layout.page-header>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    <x-ui.card title="Configuration">
        <div class="space-y-4">
            <p class="text-sm" style="color: var(--color-text-muted)">
                Add the following variables to your <code>.env</code> file:
            </p>

            <pre class="text-xs p-3 rounded-lg overflow-x-auto"
                 style="background: var(--color-bg-tertiary); color: var(--color-text-primary); border: 1px solid var(--color-border);">STEAM_API_KEY=your_key_here
STEAM_ID=your_steam_id_here</pre>

            <table class="table">
                <thead>
                    <tr>
                        <th>Variable</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-sm font-mono" style="color: var(--color-text-primary)">STEAM_API_KEY</td>
                        <td>
                            @if($apiKeyConfigured)
                                <span style="color: #4ade80;">✓ Configured</span>
                            @else
                                <span style="color: #f87171;">✗ Not configured</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-sm font-mono" style="color: var(--color-text-primary)">STEAM_ID</td>
                        <td>
                            @if($steamId)
                                <span style="color: #4ade80;">✓ {{ $steamId }}</span>
                            @else
                                <span style="color: #f87171;">✗ Not configured</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>

            <div x-data="{ result: null, loading: false }">
                <button
                    @click="
                        loading = true;
                        result = null;
                        fetch('{{ route('steam.test-connection') }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '', 'Accept': 'application/json' }
                        })
                        .then(r => r.json())
                        .then(d => { result = d; loading = false; })
                        .catch(() => { result = { success: false, error: 'Request failed.' }; loading = false; });
                    "
                    class="btn btn--primary btn--sm"
                    :disabled="loading"
                >
                    <span x-show="!loading">Test Connection</span>
                    <span x-show="loading">Testing…</span>
                </button>

                <div x-show="result !== null" class="mt-3 px-4 py-3 rounded-lg text-sm"
                     :style="result?.success
                        ? 'background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25);'
                        : 'background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.25);'"
                     style="display:none;">
                    <span x-text="result?.success
                        ? '✓ Connection successful — ' + result.game_count + ' games found.'
                        : '✗ ' + result?.error"></span>
                </div>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="How to set up">
        <div class="space-y-5 text-sm" style="color: var(--color-text-muted)">
            <div>
                <p class="font-semibold mb-2" style="color: var(--color-text-primary)">Steam API Key</p>
                <ol class="space-y-1 list-decimal list-inside">
                    <li>Go to <a href="https://steamcommunity.com/dev/apikey" target="_blank" rel="noopener"
                                 style="color: var(--color-brand)">steamcommunity.com/dev/apikey</a></li>
                    <li>Log in with your Steam account</li>
                    <li>Fill in any domain name (e.g. <code>localhost</code>)</li>
                    <li>Copy the generated API key to <code>STEAM_API_KEY</code></li>
                </ol>
            </div>

            <div>
                <p class="font-semibold mb-2" style="color: var(--color-text-primary)">Steam ID</p>
                <ol class="space-y-1 list-decimal list-inside">
                    <li>Open your Steam profile in a browser</li>
                    <li>Go to <strong>Edit Profile → General</strong></li>
                    <li>Your Steam ID appears in the URL: <code>steamcommunity.com/profiles/<strong>76561…</strong></code></li>
                    <li>Copy the 64-bit number to <code>STEAM_ID</code></li>
                </ol>
                <p class="mt-2">If your profile uses a custom URL, the "Test Connection" will resolve it automatically once you've set your API key.</p>
            </div>
        </div>
    </x-ui.card>

</div>

</x-layouts.app>
