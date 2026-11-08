<?php

declare(strict_types=1);

namespace App\Actions\Waze;

use App\Models\WazeDrive;
use App\Models\WazeWaypoint;
use App\Services\Waze\WazeCsvParser;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use ZipArchive;

final class ImportWazeDrivesAction
{
    private const int MATCH_TOLERANCE_SECONDS = 300;

    public function __construct(
        private readonly WazeCsvParser $parser,
    ) {}

    public function handle(UploadedFile $file, ?string $password = null): ImportResult
    {
        $csvContents = $this->extractCsvContents($file, $password);

        $drives = [];
        $tracks = [];
        $waypoints = [];

        foreach ($csvContents as $csv) {
            array_push($drives, ...$this->parser->parse($csv));
            array_push($tracks, ...$this->parser->parseLocationTracks($csv));
            array_push($waypoints, ...$this->parser->parseAllWaypoints($csv));
        }

        $drives = $this->enrichDrives($drives, $tracks);

        $imported = 0;
        $skipped = 0;
        $enriched = 0;

        foreach ($drives as $drive) {
            $existing = WazeDrive::where('driven_at', $drive['driven_at'])->first();

            if ($existing === null) {
                WazeDrive::create($drive);
                $imported++;

                continue;
            }

            if ($existing->origin_lat === null && $drive['origin_lat'] !== null) {
                $existing->update([
                    'ended_at' => $drive['ended_at'],
                    'origin_lat' => $drive['origin_lat'],
                    'origin_lon' => $drive['origin_lon'],
                    'distance_km' => $drive['distance_km'],
                ]);
                $enriched++;

                continue;
            }

            if ($existing->ended_at === null && $drive['ended_at'] !== null) {
                $existing->update(['ended_at' => $drive['ended_at']]);
                $enriched++;

                continue;
            }

            $skipped++;
        }

        $matchedDrivenAts = array_map(fn (array $d): Carbon => $d['driven_at'], $drives);

        foreach ($tracks as $track) {
            if ($this->hasMatchingDrive($track['driven_at'], $matchedDrivenAts)) {
                continue;
            }

            $exists = WazeDrive::where('driven_at', $track['driven_at'])->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            WazeDrive::create($track);
            $imported++;
        }

        $routeOffset = WazeWaypoint::max('route_id') ?? 0;
        $allDrives = WazeDrive::query()->get(['id', 'driven_at']);

        foreach ($waypoints as $routeIndex => $route) {
            $routeId = $routeOffset + $routeIndex + 1;
            $driveId = null;

            if (isset($tracks[$routeIndex])) {
                $driveId = $this->findNearestDrive($tracks[$routeIndex]['driven_at'], $allDrives)?->id;
            }

            WazeWaypoint::insert(array_map(
                fn (array $p): array => ['route_id' => $routeId, 'waze_drive_id' => $driveId, 'lat' => $p[0], 'lon' => $p[1]],
                $route,
            ));
        }

        return new ImportResult(imported: $imported, skipped: $skipped, enriched: $enriched);
    }

    /**
     * @param  Collection<int, WazeDrive>  $drives
     */
    private function findNearestDrive(Carbon $trackAt, Collection $drives): ?WazeDrive
    {
        return $drives
            ->filter(fn (WazeDrive $d): bool => abs($d->driven_at->diffInSeconds($trackAt)) <= self::MATCH_TOLERANCE_SECONDS)
            ->sortBy(fn (WazeDrive $d): float => abs($d->driven_at->diffInSeconds($trackAt)))
            ->first();
    }

    /**
     * Fill missing origin coordinates in Drive summary entries
     * using the nearest Location details track (within 5 minutes).
     *
     * @param  list<array{driven_at: Carbon, origin_name: string|null, origin_lat: float|null, origin_lon: float|null, destination_name: string|null, destination_lat: float|null, destination_lon: float|null, distance_km: float|null}>  $drives
     * @param  list<array{driven_at: Carbon, ended_at: Carbon, origin_lat: float, origin_lon: float, destination_lat: float, destination_lon: float, distance_km: float}>  $tracks
     * @return list<array{driven_at: Carbon, ended_at: Carbon|null, origin_name: string|null, origin_lat: float|null, origin_lon: float|null, destination_name: string|null, destination_lat: float|null, destination_lon: float|null, distance_km: float|null}>
     */
    private function enrichDrives(array $drives, array $tracks): array
    {
        foreach ($drives as &$drive) {
            $match = $this->findNearestTrack($drive['driven_at'], $tracks);

            if ($match === null) {
                $drive['ended_at'] = null;

                continue;
            }

            $drive['ended_at'] = $match['ended_at'];

            if ($drive['origin_lat'] !== null) {
                continue;
            }

            $drive['origin_lat'] = $match['origin_lat'];
            $drive['origin_lon'] = $match['origin_lon'];
            $drive['distance_km'] = $match['distance_km'];
        }

        return $drives;
    }

    /**
     * @param  list<array{driven_at: Carbon, ended_at: Carbon, origin_lat: float, origin_lon: float, destination_lat: float, destination_lon: float, distance_km: float}>  $tracks
     * @return array{driven_at: Carbon, ended_at: Carbon, origin_lat: float, origin_lon: float, destination_lat: float, destination_lon: float, distance_km: float}|null
     */
    private function findNearestTrack(Carbon $drivenAt, array $tracks): ?array
    {
        $best = null;
        $minDiff = PHP_INT_MAX;

        foreach ($tracks as $track) {
            $diff = abs($track['driven_at']->diffInSeconds($drivenAt));

            if ($diff < $minDiff && $diff <= self::MATCH_TOLERANCE_SECONDS) {
                $minDiff = $diff;
                $best = $track;
            }
        }

        return $best;
    }

    /** @param list<Carbon> $drivenAts */
    private function hasMatchingDrive(Carbon $trackAt, array $drivenAts): bool
    {
        foreach ($drivenAts as $drivenAt) {
            if (abs($trackAt->diffInSeconds($drivenAt)) <= self::MATCH_TOLERANCE_SECONDS) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function extractCsvContents(UploadedFile $file, ?string $password): array
    {
        if ($file->getClientOriginalExtension() === 'zip') {
            return $this->extractFromZip($file->getRealPath(), $password);
        }

        $content = file_get_contents($file->getRealPath());

        return $content !== false ? [$content] : [];
    }

    /** @return list<string> */
    private function extractFromZip(string $path, ?string $password): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return [];
        }

        if ($password !== null && $password !== '') {
            $zip->setPassword($password);
        }

        $contents = [];
        $matched = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if ($name === false) {
                continue;
            }

            if (! str_starts_with(basename($name), 'account_activity') || ! str_ends_with($name, '.csv')) {
                continue;
            }

            $matched++;
            $content = $zip->getFromIndex($i);

            if ($content !== false && $content !== '') {
                $contents[] = $content;
            }
        }

        $zip->close();

        if ($matched > 0 && $contents === []) {
            throw new \RuntimeException('Could not read files from the ZIP archive. Check the password and try again.');
        }

        return $contents;
    }
}
