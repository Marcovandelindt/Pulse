<x-layouts.app title="Steam Settings">

<x-layout.page-header title="Steam Settings">
    <x-slot:actions>
        <a href="{{ route('steam.index') }}" class="btn btn--secondary btn--sm">← Back</a>
    </x-slot:actions>
</x-layout.page-header>

@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
         style="background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25);">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
         style="background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.25);">
        {{ session('error') }}
    </div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Linked accounts --}}
    <x-ui.card title="Linked Accounts">
        @if($accounts->isEmpty())
            <p class="text-sm mb-4" style="color: var(--color-text-muted)">No accounts linked yet.</p>
        @else
            <div class="space-y-3 mb-4">
                @foreach($accounts as $account)
                    <div class="flex items-center gap-3 px-3 py-2 rounded-lg"
                         style="background: var(--color-bg-tertiary); border: 1px solid var(--color-border);">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium" style="color: var(--color-text-primary)">
                                    {{ $account->label }}
                                </span>
                                @if($account->is_active)
                                    <span class="text-xs px-1.5 py-0.5 rounded"
                                          style="background: rgba(34,197,94,0.15); color: #4ade80;">active</span>
                                @endif
                            </div>
                            <div class="text-xs mt-0.5" style="color: var(--color-text-muted)">
                                ID: {{ $account->steam_id }} &middot; {{ $account->games()->count() }} games
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            {{-- Test connection --}}
                            <div x-data="{ result: null, loading: false }">
                                <button
                                    @click="
                                        loading = true; result = null;
                                        fetch('{{ route('steam.test-connection') }}', {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                                                'Accept': 'application/json',
                                                'Content-Type': 'application/json'
                                            },
                                            body: JSON.stringify({ account_id: {{ $account->id }} })
                                        })
                                        .then(r => r.json())
                                        .then(d => { result = d; loading = false; })
                                        .catch(() => { result = { success: false, error: 'Request failed.' }; loading = false; });
                                    "
                                    class="btn btn--secondary btn--sm"
                                    :disabled="loading"
                                    :title="result ? (result.success ? '✓ ' + result.game_count + ' games' : '✗ ' + result.error) : 'Test connection'"
                                >
                                    <span x-show="!loading && !result">Test</span>
                                    <span x-show="loading">…</span>
                                    <span x-show="result?.success" style="color: #4ade80;">✓</span>
                                    <span x-show="result && !result.success" style="color: #f87171;">✗</span>
                                </button>
                            </div>

                            {{-- Activate --}}
                            @unless($account->is_active)
                                <form method="POST" action="{{ route('steam.accounts.activate', $account) }}">
                                    @csrf
                                    <button type="submit" class="btn btn--secondary btn--sm">Switch</button>
                                </form>
                            @endunless

                            {{-- Delete --}}
                            <form method="POST" action="{{ route('steam.accounts.destroy', $account) }}"
                                  onsubmit="return confirm('Remove {{ addslashes($account->label) }}? This will also delete all synced games for this account.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--danger btn--sm btn--icon" title="Remove">✕</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    {{-- Add account --}}
    <x-ui.card title="Add Account">
        <form method="POST" action="{{ route('steam.accounts.store') }}" class="space-y-4">
            @csrf

            <div class="form-group">
                <label class="form-label" for="label">Label</label>
                <input type="text" id="label" name="label" value="{{ old('label') }}"
                       class="form-input" placeholder="e.g. Marco, Alt Account">
                <x-form.error name="label" />
            </div>

            <div class="form-group">
                <label class="form-label" for="steam_id">Steam ID</label>
                <input type="text" id="steam_id" name="steam_id" value="{{ old('steam_id') }}"
                       class="form-input" placeholder="e.g. 76561198000000000">
                <p class="text-xs mt-1" style="color: var(--color-text-muted)">
                    64-bit Steam ID from your profile URL: <code>steamcommunity.com/profiles/<strong>76561…</strong></code>
                </p>
                <x-form.error name="steam_id" />
            </div>

            <div class="form-group">
                <label class="form-label" for="api_key">API Key</label>
                <input type="password" id="api_key" name="api_key" value="{{ old('api_key') }}"
                       class="form-input" placeholder="Steam Web API key">
                <p class="text-xs mt-1" style="color: var(--color-text-muted)">
                    Get one at <a href="https://steamcommunity.com/dev/apikey" target="_blank" rel="noopener"
                                  style="color: var(--color-brand)">steamcommunity.com/dev/apikey</a>.
                    Stored encrypted.
                </p>
                <x-form.error name="api_key" />
            </div>

            <button type="submit" class="btn btn--primary">Add Account</button>
        </form>
    </x-ui.card>

    {{-- Instructions --}}
    <x-ui.card title="How to find your Steam ID">
        <div class="space-y-4 text-sm" style="color: var(--color-text-muted)">
            <ol class="space-y-1 list-decimal list-inside">
                <li>Open your Steam profile in a browser</li>
                <li>Go to <strong>Edit Profile → General</strong></li>
                <li>Your Steam ID is the 64-bit number in the URL:<br>
                    <code class="text-xs">steamcommunity.com/profiles/<strong style="color: var(--color-text-primary)">76561…</strong></code>
                </li>
                <li>If your profile uses a custom URL: open Steam → Settings → Interface and enable "Display Steam URL address bar"</li>
            </ol>
        </div>
    </x-ui.card>

</div>

</x-layouts.app>
