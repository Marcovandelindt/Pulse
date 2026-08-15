<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Models\PlayStationCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlayStationCategoryController extends Controller
{
    public function index(): View
    {
        $categories = PlayStationCategory::withCount('games')->orderBy('name')->get();

        return view('pages.playstation.categories', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:play_station_categories,name'],
        ]);

        PlayStationCategory::create(['name' => $request->input('name')]);

        return redirect()->route('playstation.categories.index')->with('success', 'Category created.');
    }

    public function destroy(PlayStationCategory $playStationCategory): RedirectResponse
    {
        $playStationCategory->delete();

        return redirect()->route('playstation.categories.index')->with('success', 'Category deleted.');
    }
}
