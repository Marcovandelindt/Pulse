<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Actions\Dashboard\BuildAdaptiveSubtitle;
use App\Actions\Dashboard\BuildDailyBrief;
use App\Actions\Dashboard\BuildDashboardTimeline;
use App\Actions\Dashboard\GetCurrentlyPlaying;
use App\Actions\Dashboard\GetCurrentSleepStreak;
use App\Actions\Dashboard\GetCurrentStepStreak;
use App\Actions\Dashboard\GetLastYearSnapshot;
use App\Actions\Dashboard\GetPersonalRecords;
use App\Actions\Dashboard\GetRecentMilestone;
use App\Actions\Dashboard\GetWeekStats;
use App\Actions\Dashboard\GetWeekTopArtist;
use App\Actions\PlayStation\SyncGamingPresenceAction;
use App\Http\Controllers\Controller;
use App\Models\HealthEntry;
use App\Models\HealthSleep;
use App\Models\Play;
use App\Models\PlayStationSession;
use App\Models\StepGoal;
use App\Services\PlayStation\PsnPresenceService;
use App\Services\Weather\WeatherService;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly PsnPresenceService $psnPresence,
        private readonly SyncGamingPresenceAction $syncPresence,
        private readonly WeatherService $weatherService,
        private readonly GetCurrentlyPlaying $getCurrentlyPlaying,
        private readonly BuildDashboardTimeline $buildTimeline,
        private readonly GetCurrentStepStreak $stepStreak,
        private readonly GetCurrentSleepStreak $sleepStreak,
        private readonly GetLastYearSnapshot $lastYearSnapshot,
        private readonly GetRecentMilestone $recentMilestone,
        private readonly GetWeekTopArtist $weekTopArtist,
        private readonly GetPersonalRecords $personalRecords,
        private readonly GetWeekStats $weekStats,
        private readonly BuildDailyBrief $buildDailyBrief,
        private readonly BuildAdaptiveSubtitle $buildAdaptiveSubtitle,
    ) {}

    public function index(): View
    {
        $hour = now()->hour;
        $greeting = match (true) {
            $hour >= 5 && $hour < 12 => 'Good morning',
            $hour >= 12 && $hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };

        $todaySteps = (int) (HealthEntry::whereDate('date', today())->value('steps') ?? 0);
        $lastSleep = HealthSleep::orderByDesc('date')->first();
        $currentGoal = StepGoal::current();

        $stepStreak = $this->stepStreak->handle();
        $sleepStreak = $this->sleepStreak->handle();
        $adaptiveSubtitle = $this->buildAdaptiveSubtitle->handle($todaySteps, $currentGoal, $lastSleep);
        $dailyBrief = $this->buildDailyBrief->handle($todaySteps, $currentGoal, $lastSleep);

        $currentlyPlaying = $this->getCurrentlyPlaying->handle();

        try {
            $currentGame = $this->psnPresence->getCurrentGame();
            // Sync presence inline so stale records are closed before reading started_at.
            // Passes the already-fetched game to avoid a second PSN API call.
            $activePresence = $this->syncPresence->handle($currentGame);
            $gamingStartedAt = $activePresence?->started_at;
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch PSN presence', ['error' => $e->getMessage()]);
            $currentGame = null;
            $gamingStartedAt = null;
        }

        $lastPlayedSession = $currentGame === null
            ? PlayStationSession::with('game')->latest('started_at')->first()
            : null;

        return view('pages.dashboard.index', [
            'greeting' => $greeting,
            'adaptiveSubtitle' => $adaptiveSubtitle,
            'stepStreak' => $stepStreak,
            'sleepStreak' => $sleepStreak,
            'timeline' => $this->buildTimeline->handle(),
            'currentlyPlaying' => $currentlyPlaying,
            'recentPlay' => $currentlyPlaying === null
                ? Play::with(['track.album', 'track.artists'])->orderByDesc('played_at')->first()
                : null,
            'currentGame' => $currentGame,
            'gamingStartedAt' => $gamingStartedAt,
            'lastPlayedGame' => $lastPlayedSession ? [
                'title' => $lastPlayedSession->game->label,
                'platform' => $lastPlayedSession->game->platform,
                'image_url' => $lastPlayedSession->game->image_url,
            ] : null,
            'lastPlayedAt' => $lastPlayedSession?->started_at,
            'lastPlayedGameUrl' => $lastPlayedSession
                ? route('playstation.show', $lastPlayedSession->game)
                : null,
            'weather' => $this->weatherService->getCurrent(),
            'lastYear' => $this->lastYearSnapshot->handle(),
            'dailyBrief' => $dailyBrief,
            'weekTopArtist' => $this->weekTopArtist->handle(),
            'recentMilestone' => $this->recentMilestone->handle($stepStreak, $sleepStreak),
            'personalRecords' => $this->personalRecords->handle(),
            ...$this->weekStats->handle(),
        ]);
    }
}
