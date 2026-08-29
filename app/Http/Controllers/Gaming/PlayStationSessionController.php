<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\PlayStation\GetDailyPlayStationActivity;
use App\Http\Controllers\Controller;
use App\Models\PlayStationCategory;
use App\Models\PlayStationSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlayStationSessionController extends Controller
{
    public function index(Request $request): View
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

        $sessions   = $query->paginate(30)->withQueryString();
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

    public function daily(Request $request, GetDailyPlayStationActivity $action): JsonResponse
    {
        $date = $request->get('date', today()->toDateString());

        return response()->json($action->handle($date));
    }
}
