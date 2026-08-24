<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Enums\BacklogStatus;
use App\Enums\PlayMode;
use App\Http\Controllers\Controller;
use App\Models\PlayStationCategory;
use App\Models\PlayStationGame;
use App\Models\PlayStationSession;
use App\Models\PlayStationTrophy;
use App\Services\PlayStation\PlayStationScraperService;
use App\Services\PlayStation\PsnPresenceService;
use App\Services\PlayStation\PsnProfilesScraperService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class PlayStationController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->get('sort', 'hours');
        $platform = $request->get('platform');
        $search = $request->get('search', '');

        $baseQuery = PlayStationGame::query();

        if ($platform) {
            $baseQuery->where('platform', $platform);
        }

        if ($search !== '') {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%");
            });
        }

        $totalMinutes = (int) (clone $baseQuery)
            ->join('play_station_sessions', 'play_station_games.id', '=', 'play_station_sessions.play_station_game_id')
            ->sum('play_station_sessions.duration_minutes');
        $totalHours = round($totalMinutes / 60, 1);
        $totalGames = (clone $baseQuery)->count();
        $totalSessions = (clone $baseQuery)->sum('sessions');

        $query = match ($sort) {
            'name' => (clone $baseQuery)->orderBy('name'),
            'last_played' => (clone $baseQuery)->orderByDesc('last_played_at'),
            'completion' => (clone $baseQuery)->orderByDesc('completion_percentage'),
            default => (clone $baseQuery)->orderByDesc('hours'),
        };

        $games = $query->withCount([
            'trophyList',
            'trophyList as earned_trophy_count' => fn ($q) => $q->where('is_earned', true),
        ])->paginate(24)->withQueryString();

        $recentSessions = PlayStationSession::with('game')
            ->whereHas('game')
            ->latest('started_at')
            ->limit(5)
            ->get();

        $fallbacks = ['ps1.jpg', 'ps2.webp', 'ps3.jpg', 'ps4.jpg', 'ps5.jpg'];

        $sleepItems = PlayStationGame::orderByDesc('hours')
            ->get(['id', 'name', 'display_name', 'platform', 'image_url', 'hours', 'completion_percentage', 'last_played_at'])
            ->map(fn ($g) => [
                'title'       => $g->label,
                'platform'    => $g->platform,
                'image_url'   => $g->image_url ?? '/images/playstation/'.$fallbacks[$g->id % 5],
                'hours'       => round((float) $g->hours, 1),
                'completion'  => round((float) $g->completion_percentage, 0),
                'last_played' => $g->last_played_at?->format('d M Y'),
                'url'         => route('playstation.show', $g),
            ]);

        try {
            $currentGame = app(PsnPresenceService::class)->getCurrentGame();
        } catch (\Throwable) {
            $currentGame = null;
        }

        return view('pages.playstation.index', compact(
            'games',
            'sort',
            'platform',
            'search',
            'totalHours',
            'totalGames',
            'totalSessions',
            'recentSessions',
            'sleepItems',
            'currentGame',
        ));
    }

    public function show(PlayStationGame $playStationGame): View
    {
        $playStationGame->load('playSessions', 'categories', 'trophyList');

        $recentSessions = $playStationGame->playSessions()->latest('started_at')->paginate(20);

        $monthlyStats = $playStationGame->playSessions()
            ->selectRaw("DATE_FORMAT(started_at, '%Y-%m') as month, SUM(duration_minutes) as total_minutes")
            ->groupByRaw("DATE_FORMAT(started_at, '%Y-%m')")
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'total_minutes' => (int) $row->total_minutes,
                'hours' => round($row->total_minutes / 60, 1),
            ]);

        return view('pages.playstation.show', [
            'game'           => $playStationGame,
            'recentSessions' => $recentSessions,
            'monthlyStats'   => $monthlyStats,
        ]);
    }

    public function create(): View
    {
        return view('pages.playstation.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'in:PS3,PS4,PS5,PSVITA'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'manual_minutes' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:10240'],
        ]);

        $imageUrl = null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('playstation', 'public');
            $imageUrl = Storage::url($path);
        }

        PlayStationGame::create([
            'name' => $validated['name'],
            'platform' => $validated['platform'],
            'price' => $validated['price'] ?? null,
            'manual_minutes' => $validated['manual_minutes'] ?? 0,
            'image_url' => $imageUrl,
        ]);

        return redirect()->route('playstation.index')->with('success', 'Game added successfully.');
    }

    public function edit(PlayStationGame $playStationGame): View
    {
        $categories = PlayStationCategory::orderBy('name')->get();

        return view('pages.playstation.edit', [
            'game' => $playStationGame,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, PlayStationGame $playStationGame): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:255'],
            'psnprofiles_slug' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'manual_minutes' => ['nullable', 'integer', 'min:0'],
            'user_rating' => ['nullable', 'numeric', 'between:0,10'],
            'critic_rating' => ['nullable', 'numeric', 'between:0,10'],
            'backlog_status' => ['nullable', 'in:'.implode(',', array_column(BacklogStatus::cases(), 'value'))],
            'play_mode' => ['nullable', 'array'],
            'play_mode.*' => ['in:'.implode(',', array_column(PlayMode::cases(), 'value'))],
            'main_story_completed' => ['nullable', 'boolean'],
            'exclude_from_sync' => ['nullable', 'boolean'],
            'completion_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'image' => ['nullable', 'image', 'max:10240'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:play_station_categories,id'],
        ]);

        if ($request->hasFile('image')) {
            if ($playStationGame->image_url && str_starts_with($playStationGame->image_url, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $playStationGame->image_url));
            }

            $path = $request->file('image')->store('playstation', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        $categoryIds = $validated['categories'] ?? [];
        unset($validated['image'], $validated['categories']);

        $playStationGame->update($validated);
        $playStationGame->categories()->sync($categoryIds);

        return redirect()->route('playstation.show', $playStationGame)->with('success', 'Game updated.');
    }

    public function favorite(PlayStationGame $playStationGame): JsonResponse
    {
        $playStationGame->update(['is_favorite' => ! $playStationGame->is_favorite]);

        return response()->json(['is_favorite' => $playStationGame->is_favorite]);
    }

    public function sessions(Request $request): View
    {
        $search      = $request->get('search', '');
        $minDuration = $request->integer('min_duration', 0) ?: null;
        $categoryId  = $request->integer('category_id', 0) ?: null;
        $dateFrom    = $request->get('date_from');
        $dateTo      = $request->get('date_to');

        $query = PlayStationSession::with('game')->latest('started_at');

        if ($search !== '') {
            $query->whereHas('game', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($minDuration) {
            $query->where('duration_minutes', '>=', $minDuration);
        }

        if ($categoryId) {
            $query->whereHas('game.categories', fn ($q) => $q->where('play_station_categories.id', $categoryId));
        }

        if ($dateFrom && $dateTo) {
            $query->whereBetween('started_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);
        } elseif ($dateFrom) {
            $query->whereDate('started_at', $dateFrom);
        }

        $totalSessions  = (clone $query)->count();
        $avgDuration    = (int) round((float) ((clone $query)->avg('duration_minutes') ?? 0));
        $longestSession = (int) ((clone $query)->max('duration_minutes') ?? 0);
        $totalHours     = round((float) ((clone $query)->sum('duration_minutes') ?? 0) / 60, 1);

        $sessions = $query->paginate(30)->withQueryString();

        $categories = PlayStationCategory::orderBy('name')->pluck('name', 'id');

        return view('pages.playstation.sessions', compact(
            'sessions',
            'totalSessions',
            'avgDuration',
            'longestSession',
            'totalHours',
            'categories',
            'search',
            'minDuration',
            'categoryId',
            'dateFrom',
            'dateTo',
        ));
    }

    public function sync(): RedirectResponse
    {
        $username = config('services.playstation.username');
        $cookie = config('services.playstation.cookie');

        if (! $username) {
            return redirect()->back()->with('error', 'PlayStation username not configured.');
        }

        try {
            $scraper = app(PlayStationScraperService::class);

            if ($cookie) {
                $scraper->setSessionCookie($cookie);
            }

            $synced = $scraper->syncGames($username);

            return redirect()->back()->with('success', "Synced {$synced} games.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Sync failed: '.$e->getMessage());
        }
    }

    public function fetchTrophies(PlayStationGame $playStationGame): RedirectResponse
    {
        try {
            $result  = app(PsnProfilesScraperService::class)->fetchAndStore($playStationGame);
            $count   = $result['count'];
            $message = $result['user_page']
                ? "{$count} trophies geladen inclusief earned status."
                : "{$count} trophies geladen (geen earned status — zoek je PSN-naam éénmalig op op psnprofiles.com om dat te activeren).";

            return redirect()
                ->route('playstation.show', $playStationGame)
                ->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()
                ->route('playstation.show', $playStationGame)
                ->with('error', $e->getMessage());
        }
    }

    public function toggleTrophy(PlayStationTrophy $playStationTrophy): JsonResponse
    {
        $isEarned = ! $playStationTrophy->is_earned;

        $playStationTrophy->update([
            'is_earned' => $isEarned,
            'earned_at' => $isEarned ? now() : null,
        ]);

        return response()->json([
            'is_earned' => $isEarned,
            'earned_at' => $isEarned ? $playStationTrophy->fresh()->earned_at->format('d M Y') : null,
        ]);
    }

    public function dailyActivity(Request $request): JsonResponse
    {
        $date = $request->get('date', today()->toDateString());

        $activity = PlayStationSession::with('game')
            ->whereDate('started_at', $date)
            ->get()
            ->groupBy('play_station_game_id')
            ->map(function ($sessions) {
                $game = $sessions->first()->game;

                return [
                    'game' => $game->name,
                    'platform' => $game->platform,
                    'total_minutes' => $sessions->sum('duration_minutes'),
                    'session_count' => $sessions->count(),
                ];
            })
            ->values();

        return response()->json($activity);
    }
}
