<?php

declare(strict_types=1);

namespace App\Services\Ollama;

use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\EpisodeWatch;
use App\Models\HealthEntry;
use App\Models\MovieWatch;
use App\Models\Play;
use App\Models\PlayStationSession;
use App\Models\PlayStationTrophy;
use App\Models\WorkSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class DatabaseToolService
{
    /** @return list<array<string, mixed>> */
    public function definitions(): array
    {
        return [
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_health_data',
                    'description' => 'Retrieve health data (steps, heart rate) for a specific date or date range. Use this when asked about steps walked, heart rate, or health metrics for any date — including dates older than 14 days.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'date'       => ['type' => 'string', 'description' => 'A specific date in YYYY-MM-DD format for single-day queries.'],
                            'start_date' => ['type' => 'string', 'description' => 'Start date in YYYY-MM-DD format for range queries (inclusive).'],
                            'end_date'   => ['type' => 'string', 'description' => 'End date in YYYY-MM-DD format for range queries (inclusive).'],
                        ],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_gaming_data',
                    'description' => 'Retrieve PlayStation gaming sessions for a specific date or date range. Use when asked about games played, gaming hours, or gaming activity for any date.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'date'       => ['type' => 'string', 'description' => 'A specific date in YYYY-MM-DD format.'],
                            'start_date' => ['type' => 'string', 'description' => 'Start date in YYYY-MM-DD format for range queries.'],
                            'end_date'   => ['type' => 'string', 'description' => 'End date in YYYY-MM-DD format for range queries.'],
                        ],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_music_data',
                    'description' => 'Retrieve music listening history (plays, artists, tracks) for a specific date or date range. Use when asked about songs played, listening stats, or music activity for any date.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'date'       => ['type' => 'string', 'description' => 'A specific date in YYYY-MM-DD format.'],
                            'start_date' => ['type' => 'string', 'description' => 'Start date in YYYY-MM-DD format for range queries.'],
                            'end_date'   => ['type' => 'string', 'description' => 'End date in YYYY-MM-DD format for range queries.'],
                        ],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_media_data',
                    'description' => 'Retrieve movies and TV episodes watched for a specific date or date range. Use when asked about what was watched on any specific date or period.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'date'       => ['type' => 'string', 'description' => 'A specific date in YYYY-MM-DD format.'],
                            'start_date' => ['type' => 'string', 'description' => 'Start date in YYYY-MM-DD format for range queries.'],
                            'end_date'   => ['type' => 'string', 'description' => 'End date in YYYY-MM-DD format for range queries.'],
                        ],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_calendar_events',
                    'description' => 'Retrieve calendar events for a specific date or date range. Use when asked about appointments, events, birthdays, plans, or what is on the calendar for any date — past or future.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'date'       => ['type' => 'string', 'description' => 'A specific date in YYYY-MM-DD format to get events for that day.'],
                            'start_date' => ['type' => 'string', 'description' => 'Start date in YYYY-MM-DD format for range queries.'],
                            'end_date'   => ['type' => 'string', 'description' => 'End date in YYYY-MM-DD format for range queries.'],
                        ],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_contacts',
                    'description' => 'Retrieve contacts (people) with their birthdays, ages, relationship types, and upcoming birthdays. Use when asked about people, birthdays, anniversaries, or relationships.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'name'             => ['type' => 'string', 'description' => 'Search contacts by name (partial match).'],
                            'upcoming_days'    => ['type' => 'integer', 'description' => 'Return only contacts whose birthday falls within the next N days.'],
                            'relationship_type' => ['type' => 'string', 'description' => 'Filter by relationship type name (e.g. "family", "friend").'],
                        ],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_trophies',
                    'description' => 'Retrieve PlayStation trophies (earned or all). Use when asked about trophies, achievements, platinum trophies, or trophy progress for any game.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'game_name'   => ['type' => 'string', 'description' => 'Filter trophies by game name (partial match).'],
                            'earned_only' => ['type' => 'boolean', 'description' => 'If true, return only earned trophies. Defaults to true.'],
                            'type'        => ['type' => 'string', 'description' => 'Filter by trophy type: platinum, gold, silver, or bronze.'],
                            'start_date'  => ['type' => 'string', 'description' => 'Return trophies earned on or after this date (YYYY-MM-DD).'],
                            'end_date'    => ['type' => 'string', 'description' => 'Return trophies earned on or before this date (YYYY-MM-DD).'],
                        ],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_work_schedule',
                    'description' => 'Retrieve the work schedule — which days and hours are work days. Use when asked about working days, work hours, schedule, or whether a given day is a work day.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'date' => ['type' => 'string', 'description' => 'Check the active schedule for a specific date (YYYY-MM-DD).'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $arguments */
    public function execute(string $name, array $arguments): string
    {
        return match ($name) {
            'get_health_data'     => $this->queryHealth($arguments),
            'get_gaming_data'     => $this->queryGaming($arguments),
            'get_music_data'      => $this->queryMusic($arguments),
            'get_media_data'      => $this->queryMedia($arguments),
            'get_calendar_events' => $this->queryCalendar($arguments),
            'get_contacts'        => $this->queryContacts($arguments),
            'get_trophies'        => $this->queryTrophies($arguments),
            'get_work_schedule'   => $this->queryWorkSchedule($arguments),
            default               => json_encode(['error' => "Unknown tool: {$name}"]) ?: '{}',
        };
    }

    /** @param array<string, mixed> $args */
    private function queryHealth(array $args): string
    {
        $query = HealthEntry::query()->orderBy('date');

        if (isset($args['date'])) {
            $query->where('date', $args['date']);
        } else {
            if (isset($args['start_date'])) {
                $query->where('date', '>=', $args['start_date']);
            }
            if (isset($args['end_date'])) {
                $query->where('date', '<=', $args['end_date']);
            }
        }

        $entries = $query->get(['date', 'steps', 'avg_heart_rate', 'resting_heart_rate']);

        if ($entries->isEmpty()) {
            return json_encode(['message' => 'No health data found for the requested period.']) ?: '{}';
        }

        $stepsEntries = $entries->whereNotNull('steps');
        $avgSteps     = $stepsEntries->isNotEmpty()
            ? (int) round((float) $stepsEntries->avg('steps'))
            : null;

        return json_encode([
            'entries'       => $entries->map(fn ($e) => [
                'date'               => (string) $e->date,
                'steps'              => $e->steps,
                'avg_heart_rate'     => $e->avg_heart_rate,
                'resting_heart_rate' => $e->resting_heart_rate,
            ])->toArray(),
            'count'         => $entries->count(),
            'average_steps' => $avgSteps,
        ]) ?: '{}';
    }

    /** @param array<string, mixed> $args */
    private function queryGaming(array $args): string
    {
        $query = PlayStationSession::query()
            ->with('game:id,display_name,name')
            ->orderByDesc('started_at');

        if (isset($args['date'])) {
            $query->whereDate('started_at', $args['date']);
        } else {
            if (isset($args['start_date'])) {
                $query->where('started_at', '>=', $args['start_date']);
            }
            if (isset($args['end_date'])) {
                $query->where('started_at', '<=', Carbon::parse($args['end_date'])->endOfDay());
            }
        }

        $sessions = $query->get(['play_station_game_id', 'duration_minutes', 'started_at']);

        if ($sessions->isEmpty()) {
            return json_encode(['message' => 'No gaming sessions found for the requested period.']) ?: '{}';
        }

        return json_encode([
            'sessions'      => $sessions->map(fn ($s) => [
                'game'             => $s->game?->display_name ?? $s->game?->name ?? 'Unknown',
                'duration_minutes' => (int) $s->duration_minutes,
                'started_at'       => Carbon::parse($s->started_at)->format('Y-m-d H:i'),
            ])->toArray(),
            'count'         => $sessions->count(),
            'total_minutes' => (int) $sessions->sum('duration_minutes'),
        ]) ?: '{}';
    }

    /** @param array<string, mixed> $args */
    private function queryMusic(array $args): string
    {
        $applyFilter = function (\Illuminate\Database\Eloquent\Builder $q) use ($args): \Illuminate\Database\Eloquent\Builder {
            if (isset($args['date'])) {
                return $q->whereDate('plays.played_at', $args['date']);
            }
            if (isset($args['start_date'])) {
                $q->where('plays.played_at', '>=', $args['start_date']);
            }
            if (isset($args['end_date'])) {
                $q->where('plays.played_at', '<=', Carbon::parse($args['end_date'])->endOfDay());
            }

            return $q;
        };

        $base = fn (): \Illuminate\Database\Eloquent\Builder => Play::query()
            ->join('tracks', 'plays.track_id', '=', 'tracks.id')
            ->join('track_artists', 'tracks.id', '=', 'track_artists.track_id')
            ->join('artists', 'track_artists.artist_id', '=', 'artists.id')
            ->whereNotNull('plays.played_at')
            ->where('track_artists.is_primary', true);

        $total = $applyFilter($base())->count();

        if ($total === 0) {
            return json_encode(['message' => 'No music plays found for the requested period.']) ?: '{}';
        }

        $topArtists = $applyFilter($base())
            ->select('artists.name', DB::raw('COUNT(*) as plays'))
            ->groupBy('artists.name')
            ->orderByDesc('plays')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['artist' => $r->name, 'plays' => (int) $r->plays])
            ->toArray();

        $topTracks = $applyFilter($base())
            ->select('tracks.title', 'artists.name as artist', DB::raw('COUNT(*) as plays'))
            ->groupBy('tracks.title', 'artists.name')
            ->orderByDesc('plays')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['track' => $r->title, 'artist' => $r->artist, 'plays' => (int) $r->plays])
            ->toArray();

        $recentPlays = $applyFilter($base())
            ->select('tracks.title', 'artists.name as artist', 'plays.played_at')
            ->orderByDesc('plays.played_at')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'track'     => $r->title,
                'artist'    => $r->artist,
                'played_at' => Carbon::parse($r->played_at)->format('Y-m-d H:i'),
            ])
            ->toArray();

        return json_encode([
            'total_plays'  => $total,
            'top_artists'  => $topArtists,
            'top_tracks'   => $topTracks,
            'recent_plays' => $recentPlays,
        ]) ?: '{}';
    }

    /** @param array<string, mixed> $args */
    private function queryMedia(array $args): string
    {
        $movieQuery = MovieWatch::query()->with('movie:id,title')->whereNotNull('watched_at');

        $episodeQuery = EpisodeWatch::query()
            ->join('tv_episodes', 'episode_watches.tv_episode_id', '=', 'tv_episodes.id')
            ->join('tv_seasons', 'tv_episodes.tv_season_id', '=', 'tv_seasons.id')
            ->join('tv_series', 'tv_seasons.tv_series_id', '=', 'tv_series.id')
            ->whereNotNull('episode_watches.watched_at')
            ->select(
                'tv_series.name as series_name',
                'tv_seasons.season_number',
                'tv_episodes.episode_number',
                'tv_episodes.name as episode_name',
                'episode_watches.watched_at',
            )
            ->orderByDesc('episode_watches.watched_at');

        if (isset($args['date'])) {
            $movieQuery->whereDate('watched_at', $args['date']);
            $episodeQuery->whereDate('episode_watches.watched_at', $args['date']);
        } else {
            if (isset($args['start_date'])) {
                $movieQuery->where('watched_at', '>=', $args['start_date']);
                $episodeQuery->where('episode_watches.watched_at', '>=', $args['start_date']);
            }
            if (isset($args['end_date'])) {
                $endOfDay = Carbon::parse($args['end_date'])->endOfDay();
                $movieQuery->where('watched_at', '<=', $endOfDay);
                $episodeQuery->where('episode_watches.watched_at', '<=', $endOfDay);
            }
        }

        $movies   = $movieQuery->get();
        $episodes = $episodeQuery->limit(50)->get();

        if ($movies->isEmpty() && $episodes->isEmpty()) {
            return json_encode(['message' => 'No movies or TV episodes found for the requested period.']) ?: '{}';
        }

        $result = [];

        if ($movies->isNotEmpty()) {
            $result['movies'] = $movies->map(fn ($w) => [
                'title'      => $w->movie?->title ?? 'Unknown',
                'watched_at' => Carbon::parse($w->watched_at)->format('Y-m-d'),
            ])->toArray();
        }

        if ($episodes->isNotEmpty()) {
            $result['episodes'] = $episodes->map(fn ($ep) => [
                'series'       => $ep->series_name,
                'code'         => sprintf('S%02dE%02d', $ep->season_number, $ep->episode_number),
                'episode_name' => $ep->episode_name,
                'watched_at'   => Carbon::parse($ep->watched_at)->format('Y-m-d H:i'),
            ])->toArray();
        }

        return json_encode($result) ?: '{}';
    }

    /** @param array<string, mixed> $args */
    private function queryCalendar(array $args): string
    {
        $query = CalendarEvent::query()
            ->with('contact:id,name')
            ->orderBy('starts_at');

        if (isset($args['date'])) {
            $day = Carbon::parse($args['date']);
            $query->where('starts_at', '<=', $day->copy()->endOfDay())
                ->where(fn ($q) => $q
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $day->copy()->startOfDay())
                );
        } else {
            if (isset($args['start_date'])) {
                $query->where('starts_at', '>=', $args['start_date']);
            }
            if (isset($args['end_date'])) {
                $query->where('starts_at', '<=', Carbon::parse($args['end_date'])->endOfDay());
            }
        }

        $events = $query->get(['id', 'title', 'description', 'starts_at', 'ends_at', 'all_day', 'type', 'contact_id']);

        if ($events->isEmpty()) {
            return json_encode(['message' => 'No calendar events found for the requested period.']) ?: '{}';
        }

        return json_encode([
            'events' => $events->map(fn ($e) => [
                'title'       => $e->title,
                'description' => $e->description,
                'starts_at'   => $e->all_day
                    ? Carbon::parse($e->starts_at)->format('Y-m-d')
                    : Carbon::parse($e->starts_at)->format('Y-m-d H:i'),
                'ends_at'     => $e->ends_at
                    ? ($e->all_day
                        ? Carbon::parse($e->ends_at)->format('Y-m-d')
                        : Carbon::parse($e->ends_at)->format('Y-m-d H:i'))
                    : null,
                'all_day'     => $e->all_day,
                'type'        => $e->type?->value,
                'contact'     => $e->contact?->name,
            ])->toArray(),
            'count' => $events->count(),
        ]) ?: '{}';
    }

    /** @param array<string, mixed> $args */
    private function queryContacts(array $args): string
    {
        $query = Contact::query()->with('relationshipType:id,name')->orderBy('name');

        if (isset($args['name'])) {
            $query->where('name', 'like', '%'.$args['name'].'%');
        }

        if (isset($args['relationship_type'])) {
            $query->whereHas('relationshipType', fn ($q) => $q->where('name', 'like', '%'.$args['relationship_type'].'%'));
        }

        $contacts = $query->get(['id', 'name', 'birthdate', 'birth_year_unknown', 'death_date', 'relationship_type_id', 'notes']);

        if ($contacts->isEmpty()) {
            return json_encode(['message' => 'No contacts found.']) ?: '{}';
        }

        $upcomingDays = isset($args['upcoming_days']) ? (int) $args['upcoming_days'] : null;

        $result = $contacts
            ->when($upcomingDays !== null, fn ($c) => $c->filter(
                fn ($contact) => $contact->daysUntilBirthday() !== null && $contact->daysUntilBirthday() <= $upcomingDays
            ))
            ->map(fn ($c) => [
                'name'              => $c->name,
                'relationship_type' => $c->relationshipType?->name,
                'birthdate'         => $c->birthdate ? $c->birthdate->format($c->birth_year_unknown ? 'F j' : 'Y-m-d') : null,
                'age'               => $c->age(),
                'deceased'          => $c->isDeceased(),
                'death_date'        => $c->death_date?->format('Y-m-d'),
                'days_until_birthday' => $c->daysUntilBirthday(),
                'next_birthday'     => $c->nextBirthday()?->format('Y-m-d'),
                'notes'             => $c->notes,
            ])
            ->values()
            ->toArray();

        return json_encode([
            'contacts' => $result,
            'count'    => count($result),
        ]) ?: '{}';
    }

    /** @param array<string, mixed> $args */
    private function queryTrophies(array $args): string
    {
        $earnedOnly = $args['earned_only'] ?? true;

        $query = PlayStationTrophy::query()
            ->with('game:id,display_name,name')
            ->orderByDesc('earned_at');

        if ($earnedOnly) {
            $query->where('is_earned', true);
        }

        if (isset($args['type'])) {
            $query->where('type', $args['type']);
        }

        if (isset($args['start_date'])) {
            $query->where('earned_at', '>=', $args['start_date']);
        }

        if (isset($args['end_date'])) {
            $query->where('earned_at', '<=', Carbon::parse($args['end_date'])->endOfDay());
        }

        if (isset($args['game_name'])) {
            $query->whereHas('game', fn ($q) => $q
                ->where('display_name', 'like', '%'.$args['game_name'].'%')
                ->orWhere('name', 'like', '%'.$args['game_name'].'%')
            );
        }

        $trophies = $query->limit(100)->get(['play_station_game_id', 'name', 'detail', 'type', 'is_earned', 'earned_at', 'rarity']);

        if ($trophies->isEmpty()) {
            return json_encode(['message' => 'No trophies found for the requested query.']) ?: '{}';
        }

        $byType = $trophies->groupBy('type')->map->count();

        return json_encode([
            'trophies' => $trophies->map(fn ($t) => [
                'game'      => $t->game?->display_name ?? $t->game?->name ?? 'Unknown',
                'name'      => $t->name,
                'detail'    => $t->detail,
                'type'      => $t->type,
                'is_earned' => $t->is_earned,
                'earned_at' => $t->earned_at ? Carbon::parse($t->earned_at)->format('Y-m-d H:i') : null,
                'rarity'    => $t->rarityLabel(),
            ])->toArray(),
            'count'    => $trophies->count(),
            'by_type'  => $byType->toArray(),
        ]) ?: '{}';
    }

    /** @param array<string, mixed> $args */
    private function queryWorkSchedule(array $args): string
    {
        $query = WorkSchedule::query()->orderBy('valid_from');

        if (isset($args['date'])) {
            $date = Carbon::parse($args['date']);
            $query->where(fn ($q) => $q
                ->whereNull('valid_from')->orWhere('valid_from', '<=', $date)
            )->where(fn ($q) => $q
                ->whereNull('valid_until')->orWhere('valid_until', '>=', $date)
            );
        }

        $schedules = $query->get(['name', 'days', 'start_time', 'end_time', 'valid_from', 'valid_until', 'active']);

        if ($schedules->isEmpty()) {
            return json_encode(['message' => 'No work schedule found.']) ?: '{}';
        }

        $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

        return json_encode([
            'schedules' => $schedules->map(fn ($s) => [
                'name'        => $s->name,
                'days'        => collect($s->days)->map(fn ($d) => $dayNames[$d] ?? $d)->toArray(),
                'start_time'  => $s->start_time,
                'end_time'    => $s->end_time,
                'valid_from'  => $s->valid_from?->format('Y-m-d'),
                'valid_until' => $s->valid_until?->format('Y-m-d'),
                'active'      => $s->active,
            ])->toArray(),
        ]) ?: '{}';
    }
}
