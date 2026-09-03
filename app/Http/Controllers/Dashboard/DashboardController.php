<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Actions\PlayStation\SyncGamingPresenceAction;
use App\Data\ActivityItem;
use App\Http\Controllers\Controller;
use App\Models\EpisodeWatch;
use App\Models\HealthEntry;
use App\Models\MovieWatch;
use App\Models\Play;
use App\Models\PlayStationSession;
use Illuminate\Support\Carbon;
use App\Services\PlayStation\PsnPresenceService;
use App\Services\Spotify\SpotifyTrackService;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    private const TIMELINE_DAYS = 7;

    public function __construct(
        private readonly SpotifyTrackService $trackService,
        private readonly PsnPresenceService $psnPresence,
        private readonly SyncGamingPresenceAction $syncPresence,
    ) {}

    public function index(): View
    {
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

        $tracksThisWeek = Play::whereBetween('played_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        try {
            $currentlyPlaying = $this->trackService->getCurrentlyPlaying();

            if ($currentlyPlaying !== null) {
                $track = \App\Models\Track::where('spotify_track_id', $currentlyPlaying['spotify_track_id'])->first()
                    ?? $this->trackService->upsertTrack($currentlyPlaying['raw_track'], fetchDetails: false);

                $currentlyPlaying['track']         = $track->load('artists');
                $currentlyPlaying['artist_models'] = $track->artists->keyBy('spotify_artist_id');
            }
        } catch (\Throwable) {
            $currentlyPlaying = null;
        }

        try {
            $currentGame = $this->psnPresence->getCurrentGame();
            // Sync presence inline so stale records are closed before reading started_at.
            // Passes the already-fetched game to avoid a second PSN API call.
            $activePresence  = $this->syncPresence->handle($currentGame);
            $gamingStartedAt = $activePresence?->started_at;
        } catch (\Throwable) {
            $currentGame     = null;
            $gamingStartedAt = null;
        }

        $lastPlayedSession = $currentGame === null
            ? PlayStationSession::with('game')->latest('started_at')->first()
            : null;

        $lastPlayedGameUrl = $lastPlayedSession
            ? route('playstation.show', $lastPlayedSession->game)
            : null;

        $recentPlay = $currentlyPlaying === null
            ? Play::with(['track.album', 'track.artists'])->orderByDesc('played_at')->first()
            : null;

        return view('pages.dashboard.index', [
            'timeline'          => $this->buildTimeline(),
            'stepsThisWeek'     => $stepsThisWeek > 0 ? number_format((int) $stepsThisWeek) : null,
            'watchtimeThisWeek' => $this->formatMinutes($episodeMinutes + $movieMinutes),
            'playtimeThisWeek'  => $this->formatMinutes($playtimeMinutes),
            'tracksThisWeek'    => $tracksThisWeek > 0 ? number_format($tracksThisWeek) : null,
            'currentlyPlaying'  => $currentlyPlaying,
            'recentPlay'        => $recentPlay,
            'currentGame'       => $currentGame,
            'gamingStartedAt'   => $gamingStartedAt,
            'lastPlayedGame'    => $lastPlayedSession ? [
                'title'     => $lastPlayedSession->game->label,
                'platform'  => $lastPlayedSession->game->platform,
                'image_url' => $lastPlayedSession->game->image_url,
            ] : null,
            'lastPlayedAt'      => $lastPlayedSession?->started_at,
            'lastPlayedGameUrl' => $lastPlayedGameUrl,
        ]);
    }

    /** @return Collection<int, ActivityItem> */
    private function buildTimeline(): Collection
    {
        $watches = EpisodeWatch::with(['episode.season.series'])
            ->whereNotNull('watched_at')
            ->where('watched_at', '>=', now()->subDays(self::TIMELINE_DAYS)->startOfDay())
            ->orderByDesc('watched_at')
            ->get();

        // Group by series + calendar date so binge sessions collapse into one item
        $watchActivities = $watches
            ->groupBy(fn (EpisodeWatch $w) => $w->episode->season->series->id.'_'.$w->watched_at->toDateString())
            ->map(function (Collection $group): ActivityItem {
                $first = $group->first();
                $series = $first->episode->season->series;
                $count = $group->count();

                $episodeLabel = fn (EpisodeWatch $w): string =>
                    'S'.$w->episode->season->season_number.'E'.$w->episode->episode_number.' — '.$w->episode->name;

                $subtitle = $count === 1
                    ? $episodeLabel($first)
                    : $count.' episodes';

                $episodes = $count > 1
                    ? $group->sortBy('watched_at')->map($episodeLabel)->values()->all()
                    : [];

                return new ActivityItem(
                    type: 'episode_watch',
                    title: $series->name,
                    subtitle: $subtitle,
                    imageUrl: $series->poster_url,
                    occurredAt: $first->watched_at,
                    isPinned: false,
                    episodes: $episodes,
                    url: route('tv.show', $series),
                );
            })
            ->values();

        $stepActivities = HealthEntry::withSteps()
            ->where('date', '>=', now()->subDays(self::TIMELINE_DAYS)->startOfDay())
            ->where('date', '<', today())
            ->orderByDesc('date')
            ->get()
            ->map(fn (HealthEntry $entry): ActivityItem => new ActivityItem(
                type: 'steps',
                title: number_format($entry->steps).' steps',
                subtitle: 'Daily steps',
                imageUrl: null,
                occurredAt: $entry->date->startOfDay(),
                isPinned: true,
            ));

        $movieActivities = MovieWatch::with('movie')
            ->whereNotNull('watched_at')
            ->where('watched_at', '>=', now()->subDays(self::TIMELINE_DAYS)->startOfDay())
            ->orderByDesc('watched_at')
            ->get()
            ->map(fn (MovieWatch $w): ActivityItem => new ActivityItem(
                type: 'movie_watch',
                title: $w->movie->title,
                subtitle: $w->movie->release_date?->year
                    ? 'Movie · '.$w->movie->release_date->year
                    : 'Movie',
                imageUrl: $w->movie->poster_url,
                occurredAt: $w->watched_at,
                isPinned: false,
                url: route('movies.show', $w->movie),
            ));

        $musicActivities = Play::with(['track.artists'])
            ->where('played_at', '>=', now()->subDays(self::TIMELINE_DAYS)->startOfDay())
            ->orderBy('played_at')
            ->get()
            ->groupBy(fn (Play $p) => $p->played_at->toDateString())
            ->map(function (Collection $group, string $date): ActivityItem {
                $count = $group->count();
                $tracks = $group->map(fn (Play $p) => $p->track->title.' — '.$p->track->artists_string)->values()->all();

                return new ActivityItem(
                    type: 'music',
                    title: 'Spotify',
                    subtitle: 'Listened to '.($count === 1 ? '1 track' : $count.' tracks'),
                    imageUrl: null,
                    occurredAt: Carbon::parse($date)->startOfDay(),
                    isPinned: false,
                    episodes: $tracks,
                );
            })
            ->values();

        $playstationActivities = PlayStationSession::with('game')
            ->where('started_at', '>=', now()->subDays(self::TIMELINE_DAYS)->startOfDay())
            ->orderBy('started_at')
            ->get()
            ->groupBy(fn (PlayStationSession $s) => $s->started_at->toDateString())
            ->map(function (Collection $group, string $date): ActivityItem {
                $count = $group->count();
                $totalMinutes = $group->sum('duration_minutes');

                $sessionLabel = fn (PlayStationSession $s): string =>
                    $s->game->label.' · '.$s->started_at->format('H:i').' – '.$s->end_time->format('H:i');

                if ($count === 1) {
                    $session = $group->first();

                    return new ActivityItem(
                        type: 'playstation',
                        title: $session->game->label,
                        subtitle: $session->started_at->format('H:i').' – '.$session->end_time->format('H:i').' · '.$this->formatMinutes($session->duration_minutes),
                        imageUrl: $session->game->image_url,
                        occurredAt: $session->started_at,
                        isPinned: false,
                    );
                }

                return new ActivityItem(
                    type: 'playstation',
                    title: 'PlayStation',
                    subtitle: $count.' sessions · '.$this->formatMinutes($totalMinutes),
                    imageUrl: null,
                    occurredAt: Carbon::parse($date)->startOfDay(),
                    isPinned: false,
                    episodes: $group->sortBy('started_at')->map($sessionLabel)->values()->all(),
                );
            })
            ->values();

        return $watchActivities
            ->concat($movieActivities)
            ->concat($stepActivities)
            ->concat($musicActivities)
            ->concat($playstationActivities)
            ->sort(fn (ActivityItem $a, ActivityItem $b): int => $this->compareActivities($a, $b))
            ->values();
    }

    private function compareActivities(ActivityItem $a, ActivityItem $b): int
    {
        $dateCompare = $b->occurredAt->toDateString() <=> $a->occurredAt->toDateString();
        if ($dateCompare !== 0) {
            return $dateCompare;
        }

        // Within the same date: episode watches before steps
        $pinnedCompare = $a->isPinned <=> $b->isPinned;
        if ($pinnedCompare !== 0) {
            return $pinnedCompare;
        }

        return $b->occurredAt->timestamp <=> $a->occurredAt->timestamp;
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
