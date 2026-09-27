<x-layouts.app title="PlayStation Wishlist">

<x-layout.page-header title="Wishlist">
    <x-slot:actions>
        <button @click="$dispatch('open-add-wishlist')" class="btn btn--primary btn--sm">+ Add game</button>
        <a href="{{ route('playstation.index') }}" class="btn btn--secondary btn--sm">← Back</a>
    </x-slot:actions>
</x-layout.page-header>

@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
         style="background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25);">
        {{ session('success') }}
    </div>
@endif

@php
    $wanted    = $items->whereNull('purchased_at');
    $purchased = $items->whereNotNull('purchased_at');
@endphp

@if($wanted->isNotEmpty())
    <x-ui.card class="mb-6">
        <x-slot:title>
            Want to buy
            <span style="color: var(--color-text-muted); font-weight: 400; font-size: 0.8125rem; margin-left: 0.25rem;">{{ $wanted->count() }}</span>
            @if($totalCost > 0)
                <span style="color: var(--color-text-muted); font-weight: 400; font-size: 0.8125rem; margin-left: 0.5rem;">· €{{ number_format($totalCost, 2, ',', '.') }}</span>
            @endif
        </x-slot:title>

        <div class="gaming-wishlist">
            @foreach($wanted as $item)
                <div class="gaming-wishlist-item">
                    <div class="gaming-wishlist-item__info">
                        <div class="gaming-wishlist-item__name">{{ $item->name }}</div>
                        <div class="gaming-wishlist-item__meta">
                            <span class="gaming-platform-badge" style="background: {{ \App\Models\PlayStationGame::platformColorFor($item->platform) }}">{{ $item->platform }}</span>
                            @if($item->price)
                                <span class="text-sm" style="color: var(--color-text-muted);">€{{ number_format($item->price, 2, ',', '.') }}</span>
                            @endif
                            @if($item->notes)
                                <span class="text-sm" style="color: var(--color-text-muted);">{{ $item->notes }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="gaming-wishlist-item__actions">
                        @if($item->psn_url)
                            <a href="{{ $item->psn_url }}" target="_blank" rel="noopener" class="btn btn--secondary btn--sm">PSN →</a>
                        @endif
                        <form method="POST" action="{{ route('playstation.wishlist.purchase', $item) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn--secondary btn--sm">✓ Purchased</button>
                        </form>
                        <form method="POST" action="{{ route('playstation.wishlist.destroy', $item) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn--danger btn--sm">✕</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui.card>
@else
    <x-ui.empty-state title="Wishlist is empty" description="Add games you want to buy.">
        <x-slot:action>
            <button @click="$dispatch('open-add-wishlist')" class="btn btn--primary btn--sm">+ Add game</button>
        </x-slot:action>
    </x-ui.empty-state>
@endif

@if($purchased->isNotEmpty())
    <x-ui.card>
        <x-slot:title>
            Purchased
            <span style="color: var(--color-text-muted); font-weight: 400; font-size: 0.8125rem; margin-left: 0.25rem;">{{ $purchased->count() }}</span>
        </x-slot:title>

        <div class="gaming-wishlist gaming-wishlist--purchased">
            @foreach($purchased->sortByDesc('purchased_at') as $item)
                <div class="gaming-wishlist-item gaming-wishlist-item--purchased">
                    <div class="gaming-wishlist-item__info">
                        <div class="gaming-wishlist-item__name">{{ $item->name }}</div>
                        <div class="gaming-wishlist-item__meta">
                            <span class="gaming-platform-badge" style="background: {{ \App\Models\PlayStationGame::platformColorFor($item->platform) }}">{{ $item->platform }}</span>
                            @if($item->price)
                                <span class="text-sm" style="color: var(--color-text-muted);">€{{ number_format($item->price, 2, ',', '.') }}</span>
                            @endif
                            <span class="text-sm" style="color: var(--color-text-muted);">Purchased {{ $item->purchased_at->format('d M Y') }}</span>
                        </div>
                    </div>
                    <div class="gaming-wishlist-item__actions">
                        <form method="POST" action="{{ route('playstation.wishlist.destroy', $item) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn--danger btn--sm">✕</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui.card>
@endif

{{-- Add to wishlist modal --}}
<div
    x-data="{ open: false }"
    @open-add-wishlist.window="open = true"
    @keydown.escape.window="open = false"
>
    <div x-show="open" x-transition.opacity class="modal" style="display:none;">
        <div class="modal__backdrop" @click="open = false"></div>
        <div class="modal__panel" @click.stop>
            <div class="modal__header">
                <h3 class="modal__title">Add to wishlist</h3>
                <button @click="open = false" class="modal__close">✕</button>
            </div>
            <form method="POST" action="{{ route('playstation.wishlist.store') }}" class="modal__body">
                @csrf
                <div class="form-group">
                    <label class="form-label">Game name</label>
                    <input type="text" name="name" class="form-input" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Platform</label>
                    <select name="platform" class="form-input">
                        @foreach(['PS5', 'PS4', 'PS3', 'PSVITA'] as $p)
                            <option value="{{ $p }}">{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Price (optional)</label>
                    <input type="number" name="price" step="0.01" min="0" class="form-input" placeholder="€0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">PSN Store link (optional)</label>
                    <input type="url" name="psn_url" class="form-input" placeholder="https://store.playstation.com/…">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes (optional)</label>
                    <textarea name="notes" rows="2" class="form-input"></textarea>
                </div>
                <div class="modal__footer">
                    <button type="button" @click="open = false" class="btn btn--secondary">Cancel</button>
                    <button type="submit" class="btn btn--primary">Add to wishlist</button>
                </div>
            </form>
        </div>
    </div>
</div>

</x-layouts.app>
