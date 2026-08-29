<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Album;
use App\Models\Artist;
use App\Models\SpotifyImport;
use App\Models\Track;
use App\Services\Spotify\SpotifyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessSpotifyImport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;
    public int $tries   = 1;

    public function __construct(
        private readonly SpotifyImport $import,
    ) {}

    public function handle(SpotifyService $spotify): void
    {
        $this->import->update(['status' => 'processing']);

        try {
            $raw     = json_decode(Storage::get($this->import->file_path), true, 512, JSON_THROW_ON_ERROR);
            $entries = collect($raw)->filter(
                fn ($e) => isset($e['spotify_track_uri'])
                    && str_starts_with($e['spotify_track_uri'], 'spotify:track:')
                    && ($e['ms_played'] ?? 0) >= 30_000
            )->values();

            $entries = $entries->sortBy('ts')->values();

            $this->import->update(['total_entries' => $entries->count()]);

            if ($entries->isEmpty()) {
                $this->import->update(['status' => 'done']);

                return;
            }

            $allSpotifyIds = $entries
                ->pluck('spotify_track_uri')
                ->map(fn ($uri) => str_replace('spotify:track:', '', $uri))
                ->unique()
                ->values();

            $trackIdMap = Track::whereIn('spotify_track_id', $allSpotifyIds)
                ->pluck('id', 'spotify_track_id');

            $missingIds = $allSpotifyIds->diff($trackIdMap->keys())->values();

            foreach ($missingIds->chunk(50) as $chunk) {
                $response = $spotify->get('/tracks', [
                    'ids'    => $chunk->implode(','),
                    'market' => 'NL',
                ]);

                if ($response !== null) {
                    foreach ($response['tracks'] ?? [] as $trackData) {
                        if ($trackData === null) {
                            continue;
                        }

                        $id = $this->upsertTrack($trackData);

                        if ($id !== null) {
                            $trackIdMap[$trackData['id']] = $id;
                        }
                    }
                }

                usleep(200_000);
            }

            $trackDurationMap = Track::whereIn('id', $trackIdMap->values()->toArray())
                ->pluck('duration_ms', 'id');

            $synced     = 0;
            $skipped    = 0;
            $processed  = 0;
            $batch      = [];
            $now        = now()->toDateTimeString();
            $timezone   = config('app.timezone');
            $lastSeenAt = [];

            foreach ($entries as $entry) {
                $spotifyId = str_replace('spotify:track:', '', $entry['spotify_track_uri']);
                $dbTrackId = $trackIdMap[$spotifyId] ?? null;
                $processed++;

                if ($dbTrackId === null) {
                    $skipped++;
                    continue;
                }

                $playedAt   = Carbon::parse($entry['ts'], 'UTC')->setTimezone($timezone);
                $durationMs = $trackDurationMap->get($dbTrackId);

                if ($this->isDuplicatePlay($playedAt, $lastSeenAt[$dbTrackId] ?? null, $durationMs)) {
                    $skipped++;
                    continue;
                }

                $lastSeenAt[$dbTrackId] = $playedAt;

                $batch[] = [
                    'track_id'   => $dbTrackId,
                    'played_at'  => $playedAt->toDateTimeString(),
                    'source'     => 'import',
                    'context'    => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batch) >= 500) {
                    $inserted  = DB::table('plays')->insertOrIgnore($batch);
                    $synced   += $inserted;
                    $skipped  += count($batch) - $inserted;
                    $batch     = [];

                    $this->import->update([
                        'processed' => $processed,
                        'synced'    => $synced,
                        'skipped'   => $skipped,
                    ]);
                }
            }

            if (! empty($batch)) {
                $inserted  = DB::table('plays')->insertOrIgnore($batch);
                $synced   += $inserted;
                $skipped  += count($batch) - $inserted;
            }

            $this->import->update([
                'status'    => 'done',
                'processed' => $processed,
                'synced'    => $synced,
                'skipped'   => $skipped,
            ]);

        } catch (\Throwable $e) {
            $this->import->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);
        }
    }

    private function isDuplicatePlay(Carbon $playedAt, ?Carbon $lastPlayedAt, ?int $durationMs): bool
    {
        if ($lastPlayedAt === null) {
            return false;
        }

        $gapMs    = abs($playedAt->diffInMilliseconds($lastPlayedAt));
        $minGapMs = max($durationMs ?? 0, 5_000);

        return $gapMs < $minGapMs;
    }

    private function upsertTrack(array $trackData): ?int
    {
        $artistModels = [];

        foreach ($trackData['artists'] as $index => $artistData) {
            $artistModels[] = [
                'model' => Artist::firstOrCreate(
                    ['spotify_artist_id' => $artistData['id']],
                    ['name' => $artistData['name']],
                ),
                'index' => $index,
            ];
        }

        $albumData = $trackData['album'];
        $album     = Album::firstOrCreate(
            ['spotify_album_id' => $albumData['id']],
            [
                'name'         => $albumData['name'],
                'image_url'    => $albumData['images'][0]['url'] ?? null,
                'album_type'   => $albumData['album_type'] ?? null,
                'total_tracks' => $albumData['total_tracks'] ?? null,
                'release_date' => $albumData['release_date'] ?? null,
            ],
        );

        $track = Track::firstOrCreate(
            ['spotify_track_id' => $trackData['id']],
            [
                'album_id'    => $album->id,
                'title'       => $trackData['name'],
                'duration_ms' => $trackData['duration_ms'] ?? null,
                'popularity'  => $trackData['popularity'] ?? null,
                'preview_url' => $trackData['preview_url'] ?? null,
                'spotify_uri' => $trackData['uri'] ?? null,
                'is_explicit' => $trackData['explicit'] ?? false,
            ],
        );

        $pivotData = [];
        foreach ($artistModels as $item) {
            $pivotData[$item['model']->id] = [
                'is_primary' => $item['index'] === 0,
                'sort_order' => $item['index'],
            ];
        }
        $track->artists()->syncWithoutDetaching($pivotData);

        return $track->id;
    }
}
