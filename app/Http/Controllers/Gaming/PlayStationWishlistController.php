<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gaming\StoreWishlistItemRequest;
use App\Models\PlayStationWishlistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PlayStationWishlistController extends Controller
{
    public function index(): View
    {
        $items     = PlayStationWishlistItem::orderBy('purchased_at')->orderBy('name')->get();
        $totalCost = $items->whereNull('purchased_at')->sum('price');

        return view('pages.playstation.wishlist', compact('items', 'totalCost'));
    }

    public function store(StoreWishlistItemRequest $request): RedirectResponse
    {
        PlayStationWishlistItem::create($request->validated());

        return redirect()->route('playstation.wishlist.index')->with('success', 'Added to wishlist.');
    }

    public function purchase(PlayStationWishlistItem $playStationWishlistItem): RedirectResponse
    {
        $playStationWishlistItem->update(['purchased_at' => now()]);

        return redirect()->route('playstation.wishlist.index')->with('success', "{$playStationWishlistItem->name} marked as purchased.");
    }

    public function destroy(PlayStationWishlistItem $playStationWishlistItem): RedirectResponse
    {
        $playStationWishlistItem->delete();

        return redirect()->route('playstation.wishlist.index')->with('success', 'Removed from wishlist.');
    }
}
