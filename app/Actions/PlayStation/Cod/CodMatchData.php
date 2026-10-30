<?php

declare(strict_types=1);

namespace App\Actions\PlayStation\Cod;

use App\Enums\CodResult;
use Carbon\Carbon;

final readonly class CodMatchData
{
    public function __construct(
        public string $map,
        public string $mode,
        public CodResult $result,
        public int $teamScore,
        public int $opponentScore,
        public int $score,
        public int $kills,
        public int $deaths,
        public float $kdRatio,
        public ?int $objectiveScore,
        public int $damage,
        public ?string $weaponName,
        public ?string $weaponCategory,
        public ?float $weaponAccuracy,
        public ?int $weaponKills,
        public string $relativeTime,
        public Carbon $estimatedDate,
    ) {}

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw, Carbon $screenshotTakenAt): self
    {
        return new self(
            map: (string) $raw['map'],
            mode: (string) $raw['mode'],
            result: CodResult::from(strtolower((string) $raw['result'])),
            teamScore: (int) $raw['team_score'],
            opponentScore: (int) $raw['opponent_score'],
            score: (int) $raw['score'],
            kills: (int) $raw['kills'],
            deaths: (int) $raw['deaths'],
            kdRatio: (float) $raw['kd_ratio'],
            objectiveScore: isset($raw['objective_score']) ? (int) $raw['objective_score'] : null,
            damage: (int) $raw['damage'],
            weaponName: isset($raw['weapon_name']) ? (string) $raw['weapon_name'] : null,
            weaponCategory: isset($raw['weapon_category']) ? (string) $raw['weapon_category'] : null,
            weaponAccuracy: isset($raw['weapon_accuracy']) ? (float) $raw['weapon_accuracy'] : null,
            weaponKills: isset($raw['weapon_kills']) ? (int) $raw['weapon_kills'] : null,
            relativeTime: (string) $raw['relative_time'],
            estimatedDate: self::estimateDate($raw['relative_time'], $screenshotTakenAt),
        );
    }

    private static function estimateDate(mixed $relativeTime, Carbon $takenAt): Carbon
    {
        $text = strtolower((string) $relativeTime);

        if (preg_match('/(\d+)\s*hour/i', $text, $m)) {
            return $takenAt->copy()->subHours((int) $m[1])->startOfDay();
        }

        if (preg_match('/(\d+)\s*day/i', $text, $m)) {
            return $takenAt->copy()->subDays((int) $m[1])->startOfDay();
        }

        // "just now" or "today"
        return $takenAt->copy()->startOfDay();
    }
}
