<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Actions\Dashboard\BuildAdaptiveSubtitle;
use App\Actions\Dashboard\BuildDailyBrief;
use App\Actions\Dashboard\BuildDashboardTimeline;
use App\Actions\Dashboard\GetCurrentSleepStreak;
use App\Actions\Dashboard\GetCurrentStepStreak;
use App\Actions\Dashboard\GetLastYearSnapshot;
use App\Actions\Dashboard\GetPersonalRecords;
use App\Actions\Dashboard\GetRecentMilestone;
use App\Actions\Dashboard\GetWeekTopArtist;
use App\Actions\PlayStation\SyncGamingPresenceAction;
use App\Http\Controllers\Controller;
use App\Models\EpisodeWatch;
use App\Models\Expense;
use App\Models\HealthEntry;
use App\Models\HealthSleep;
use App\Models\MovieWatch;
use App\Models\Play;
use App\Models\PlayStationSession;
use App\Models\StepGoal;
use App\Models\Track;
use App\Services\PlayStation\PsnPresenceService;
use App\Services\Spotify\SpotifyTrackService;
use App\Services\Weather\WeatherService;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly SpotifyTrackService $trackService,
        private readonly PsnPresenceService $psnPresence,
        private readonly SyncGamingPresenceAction $syncPresence,
        private readonly WeatherService $weatherService,
        private readonly BuildDashboardTimeline $buildTimeline,
        private readonly GetCurrentStepStreak $stepStreak,
        private readonly GetCurrentSleepStreak $sleepStreak,
        private readonly GetLastYearSnapshot $lastYearSnapshot,
        private readonly GetRecentMilestone $recentMilestone,
        private readonly GetWeekTopArtist $weekTopArtist,
        private readonly GetPersonalRecords $personalRecords,
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

        $stepsThisWeek = HealthEntry::withSteps()->thisWeek()->sum('steps');

        $episodeMinutes = (int) EpisodeWatch::join('tv_episodes', 'episode_watches.tv_episode_id', '=', 'tv_episodes.id')
            ->whereBetween('episode_watches.watched_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereNotNull('episode_watches.watched_at')
            ->sum('tv_episodes.runtime');

        $movieMinutes = (int) MovieWatch::join('movies', 'movie_watches.movie_id', '=', 'movies.id')
            ->whereBetween('movie_watches.watched_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereNotNull('movie_watches.watched_at')
            ->sum('movies.runtime');

        $playtimeMinutes = (int) PlayStationSession::thisWeek()->sum('duration_minutes');
        $spendingThisWeek = (float) Expense::thisWeek()->sum('amount');
        $tracksThisWeek = Play::whereBetween('played_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        try {
            $currentlyPlaying = $this->trackService->getCurrentlyPlaying();

            if ($currentlyPlaying !== null) {
                $track = Track::where('spotify_track_id', $currentlyPlaying['spotify_track_id'])->first()
                    ?? $this->trackService->upsertTrack($currentlyPlaying['raw_track'], fetchDetails: false);

                $currentlyPlaying['track'] = $track->load('artists');
                $currentlyPlaying['artist_models'] = $track->artists->keyBy('spotify_artist_id');
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch currently playing track', ['error' => $e->getMessage()]);
            $currentlyPlaying = null;
        }

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

        $weather = $this->weatherService->getCurrent();

        return view('pages.dashboard.index', [
            'greeting' => $greeting,
            'adaptiveSubtitle' => $adaptiveSubtitle,
            'stepStreak' => $stepStreak,
            'sleepStreak' => $sleepStreak,
            'timeline' => $this->buildTimeline->handle(),
            'stepsThisWeek' => $stepsThisWeek > 0 ? number_format((int) $stepsThisWeek) : null,
            'watchtimeThisWeek' => $this->formatMinutes($episodeMinutes + $movieMinutes),
            'playtimeThisWeek' => $this->formatMinutes($playtimeMinutes),
            'tracksThisWeek' => $tracksThisWeek > 0 ? number_format($tracksThisWeek) : null,
            'spendingThisWeek' => $spendingThisWeek > 0 ? '€ '.number_format($spendingThisWeek, 2, ',', '.') : null,
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
            'lastYear' => $this->lastYearSnapshot->handle(),
            'dailyBrief' => $dailyBrief,
            'weekTopArtist' => $this->weekTopArtist->handle(),
            'recentMilestone' => $this->recentMilestone->handle($stepStreak, $sleepStreak),
            'personalRecords' => $this->personalRecords->handle(),
            'weather' => $weather,
        ]);
    }

    private function formatMinutes(int $minutes): ?string
    {
        if ($minutes === 0) {
            return null;
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($h > 0 && $m > 0) {
            return "{$h}h {$m}m";
        }

        return $h > 0 ? "{$h}h" : "{$m}m";
    }
}
