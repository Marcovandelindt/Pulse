<?php

declare(strict_types=1);

namespace App\Actions\PlayStation\Cod;

use App\Models\CodMatch;

final class BuildCodFingerprint
{
    public function handle(CodMatchData $data): string
    {
        $fields = $this->fields($data);

        return hash('sha256', implode('|', $fields));
    }

    /**
     * Returns the normalised field values used for both the exact fingerprint
     * and the near-match comparison.
     *
     * @return array<string, string>
     */
    public function fields(CodMatchData $data): array
    {
        return [
            'map' => strtolower(trim($data->map)),
            'mode' => strtolower(trim($data->mode)),
            'result' => $data->result->value,
            'team_score' => (string) $data->teamScore,
            'opponent_score' => (string) $data->opponentScore,
            'score' => (string) $data->score,
            'kills' => (string) $data->kills,
            'deaths' => (string) $data->deaths,
            'objective_score' => (string) ($data->objectiveScore ?? ''),
            'damage' => (string) $data->damage,
            'weapon_name' => strtolower(trim($data->weaponName ?? '')),
        ];
    }

    /**
     * Build fingerprint fields directly from a persisted CodMatch model,
     * avoiding the need to reconstruct a CodMatchData DTO.
     *
     * @return array<string, string>
     */
    public function fieldsFromMatch(CodMatch $match): array
    {
        $weapon = $match->topWeapon;
        $weaponName = $weapon !== null ? strtolower(trim($weapon->name)) : '';

        return [
            'map' => strtolower(trim($match->map->name)),
            'mode' => strtolower(trim($match->mode->name)),
            'result' => $match->result->value,
            'team_score' => (string) $match->team_score,
            'opponent_score' => (string) $match->opponent_score,
            'score' => (string) $match->score,
            'kills' => (string) $match->kills,
            'deaths' => (string) $match->deaths,
            'objective_score' => (string) ($match->objective_score ?? ''),
            'damage' => (string) $match->damage,
            'weapon_name' => $weaponName,
        ];
    }

    /**
     * Count how many fingerprint fields match between two matches.
     * Used to detect near-duplicates where one field was misread.
     *
     * @param  array<string, string>  $a
     * @param  array<string, string>  $b
     */
    public function similarity(array $a, array $b): int
    {
        $matching = 0;

        foreach ($a as $key => $value) {
            if (isset($b[$key]) && $b[$key] === $value) {
                $matching++;
            }
        }

        return $matching;
    }
}
