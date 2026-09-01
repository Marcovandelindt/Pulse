<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Enums\BacklogStatus;
use App\Enums\PlayMode;
use App\Http\Controllers\Controller;
use App\Models\PlayStationCategory;
use App\Models\PlayStationGame;
use App\Queries\Gaming\PlayStationGameQuery;
use App\Queries\Gaming\PlayStationIndexQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class PlayStationController extends Controller
{
    public function index(Request $request, PlayStationIndexQuery $query): View
    {
        return view('pages.playstation.index', $query->handle(
            sort: $request->get('sort', 'hours'),
            platform: $request->get('platform'),
            search: $request->get('search', ''),
        ));
    }

    public function create(): View
    {
        $categories = PlayStationCategory::orderBy('name')->get();

        return view('pages.playstation.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'display_name'          => ['nullable', 'string', 'max:255'],
            'trophy_search_name'    => ['nullable', 'string', 'max:255'],
            'platform'              => ['required', 'in:PS3,PS4,PS5,PSVITA'],
            'np_communication_id'   => ['nullable', 'string', 'max:50'],
            'price'                 => ['nullable', 'numeric', 'min:0'],
            'psn_hours'             => ['nullable', 'integer', 'min:0'],
            'psn_minutes'           => ['nullable', 'integer', 'between:0,59'],
            'released_at'           => ['nullable', 'date'],
            'user_rating'           => ['nullable', 'numeric', 'between:0,10'],
            'critic_rating'         => ['nullable', 'numeric', 'between:0,10'],
            'backlog_status'        => ['nullable', 'in:'.implode(',', array_column(BacklogStatus::cases(), 'value'))],
            'play_mode'             => ['nullable', 'array'],
            'play_mode.*'           => ['in:'.implode(',', array_column(PlayMode::cases(), 'value'))],
            'main_story_completed'  => ['nullable', 'boolean'],
            'exclude_from_sync'     => ['nullable', 'boolean'],
            'completion_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'image'                 => ['nullable', 'image', 'max:10240'],
            'categories'            => ['nullable', 'array'],
            'categories.*'          => ['integer', 'exists:play_station_categories,id'],
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path     = $request->file('image')->store('playstation', 'public');
            $imageUrl = Storage::url($path);
        }

        $psnHours    = (int) ($validated['psn_hours'] ?? 0);
        $psnMins     = (int) ($validated['psn_minutes'] ?? 0);
        $categoryIds = $validated['categories'] ?? [];

        unset($validated['psn_hours'], $validated['psn_minutes'], $validated['image'], $validated['categories']);

        $validated['image_url']         = $imageUrl;
        $validated['psn_total_minutes'] = ($psnHours > 0 || $psnMins > 0) ? $psnHours * 60 + $psnMins : null;
        // Manually created games are excluded from sync by default so the scraper never overwrites them.
        $validated['exclude_from_sync'] = (bool) ($validated['exclude_from_sync'] ?? true);

        $game = PlayStationGame::create($validated);
        $game->categories()->sync($categoryIds);

        return redirect()->route('playstation.show', $game)->with('success', 'Game added successfully.');
    }

    public function show(PlayStationGame $playStationGame, PlayStationGameQuery $query): View
    {
        return view('pages.playstation.show', $query->handle($playStationGame));
    }

    public function edit(PlayStationGame $playStationGame): View
    {
        $categories = PlayStationCategory::orderBy('name')->get();

        return view('pages.playstation.edit', [
            'game'       => $playStationGame,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, PlayStationGame $playStationGame): RedirectResponse
    {
        $validated = $request->validate([
            'display_name'          => ['nullable', 'string', 'max:255'],
            'trophy_search_name'    => ['nullable', 'string', 'max:255'],
            'np_communication_id'   => ['nullable', 'string', 'max:50'],
            'price'                 => ['nullable', 'numeric', 'min:0'],
            'psn_hours'             => ['nullable', 'integer', 'min:0'],
            'psn_minutes'           => ['nullable', 'integer', 'between:0,59'],
            'user_rating'           => ['nullable', 'numeric', 'between:0,10'],
            'critic_rating'         => ['nullable', 'numeric', 'between:0,10'],
            'backlog_status'        => ['nullable', 'in:'.implode(',', array_column(BacklogStatus::cases(), 'value'))],
            'play_mode'             => ['nullable', 'array'],
            'play_mode.*'           => ['in:'.implode(',', array_column(PlayMode::cases(), 'value'))],
            'main_story_completed'  => ['nullable', 'boolean'],
            'exclude_from_sync'     => ['nullable', 'boolean'],
            'completion_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'image'                 => ['nullable', 'image', 'max:10240'],
            'categories'            => ['nullable', 'array'],
            'categories.*'          => ['integer', 'exists:play_station_categories,id'],
            'released_at'           => ['nullable', 'date'],
        ]);

        if ($request->hasFile('image')) {
            if ($playStationGame->image_url && str_starts_with($playStationGame->image_url, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $playStationGame->image_url));
            }

            $path                   = $request->file('image')->store('playstation', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        $psnHours = (int) ($validated['psn_hours'] ?? 0);
        $psnMins  = (int) ($validated['psn_minutes'] ?? 0);

        $categoryIds = $validated['categories'] ?? [];
        unset($validated['psn_hours'], $validated['psn_minutes'], $validated['image'], $validated['categories']);

        $validated['psn_total_minutes'] = ($psnHours > 0 || $psnMins > 0) ? $psnHours * 60 + $psnMins : null;

        // When trophy_search_name changes and the user hasn't manually set a new np_communication_id,
        // wipe the cached match so the next trophy fetch re-runs title matching.
        $searchNameChanged = ($validated['trophy_search_name'] ?? null) !== $playStationGame->trophy_search_name;
        $npIdExplicitlySet = ! empty($validated['np_communication_id']);
        if ($searchNameChanged && ! $npIdExplicitlySet) {
            $validated['np_communication_id'] = null;
            $validated['np_service_name']     = null;
        }

        $playStationGame->update($validated);
        $playStationGame->categories()->sync($categoryIds);

        return redirect()->route('playstation.show', $playStationGame)->with('success', 'Game updated.');
    }
}
