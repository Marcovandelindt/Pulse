<?php

declare(strict_types=1);

namespace App\Services\Tmdb;

use App\Models\Movie;
use App\Models\Person;
use App\Models\TvSeries;

final class PersonSyncService
{
    public function syncMovieCast(Movie $movie, array $credits): void
    {
        $pivot = [];

        foreach ($credits['cast'] ?? [] as $member) {
            $person = $this->findOrCreatePerson($member);
            $pivot[$person->id] = [
                'character' => $member['character'] ?? null,
                'department' => 'Acting',
                'job' => 'Actor',
                'cast_order' => $member['order'] ?? null,
            ];
        }

        foreach ($credits['crew'] ?? [] as $member) {
            if (! in_array($member['job'] ?? '', ['Director', 'Producer', 'Screenplay', 'Writer'], true)) {
                continue;
            }
            $person = $this->findOrCreatePerson($member);
            $key = $person->id.'_'.($member['job'] ?? '');
            $pivot[$key] = [
                'character' => null,
                'department' => $member['department'] ?? null,
                'job' => $member['job'] ?? null,
                'cast_order' => null,
            ];
        }

        $movie->people()->sync(array_values($pivot) === $pivot ? $pivot : array_combine(
            array_map(fn ($k) => (int) explode('_', (string) $k)[0], array_keys($pivot)),
            array_values($pivot),
        ));
    }

    public function syncTvCast(TvSeries $series, array $aggregateCredits): void
    {
        $pivot = [];

        // Crew first so cast always wins when the same person appears in both
        foreach ($aggregateCredits['crew'] ?? [] as $member) {
            if (! in_array($member['department'] ?? '', ['Directing', 'Writing', 'Production'], true)) {
                continue;
            }
            $person = $this->findOrCreatePerson($member);
            $jobs = $member['jobs'][0] ?? [];
            $pivot[$person->id] = [
                'character' => null,
                'department' => $member['department'] ?? null,
                'job' => $jobs['job'] ?? null,
                'cast_order' => null,
                'episode_count' => $member['total_episode_count'] ?? null,
            ];
        }

        foreach ($aggregateCredits['cast'] ?? [] as $member) {
            $person = $this->findOrCreatePerson($member);
            $roles = $member['roles'][0] ?? [];
            $pivot[$person->id] = [
                'character' => $roles['character'] ?? null,
                'department' => 'Acting',
                'job' => 'Actor',
                'cast_order' => $member['order'] ?? null,
                'episode_count' => $member['total_episode_count'] ?? null,
            ];
        }

        $series->people()->sync($pivot);
    }

    private function findOrCreatePerson(array $data): Person
    {
        return Person::firstOrCreate(
            ['tmdb_id' => $data['id']],
            [
                'name' => $data['name'] ?? $data['original_name'] ?? 'Unknown',
                'profile_path' => $data['profile_path'] ?? null,
            ],
        );
    }
}
