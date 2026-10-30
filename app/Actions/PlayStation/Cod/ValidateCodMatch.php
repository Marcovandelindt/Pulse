<?php

declare(strict_types=1);

namespace App\Actions\PlayStation\Cod;

use App\Enums\CodResult;

final class ValidateCodMatch
{
    private const float KD_TOLERANCE = 0.15;

    private const int   MAX_KILLS = 150;

    private const int   MAX_DEATHS = 150;

    private const int   MAX_SCORE = 100_000;

    private const int   MAX_DAMAGE = 250_000;

    private const float MAX_ACCURACY = 100.0;

    public function handle(CodMatchData $data): ValidationResult
    {
        $errors = [];

        $errors = [...$errors, ...$this->checkKdRatio($data)];
        $errors = [...$errors, ...$this->checkWeaponKills($data)];
        $errors = [...$errors, ...$this->checkResultVsScores($data)];
        $errors = [...$errors, ...$this->checkRanges($data)];

        return $errors === []
            ? ValidationResult::pass()
            : ValidationResult::fail(...$errors);
    }

    /** @return list<string> */
    private function checkKdRatio(CodMatchData $data): array
    {
        if ($data->deaths === 0) {
            $expected = (float) $data->kills;
        } else {
            $expected = round($data->kills / $data->deaths, 2);
        }

        if (abs($data->kdRatio - $expected) > self::KD_TOLERANCE) {
            return ["K/D ratio {$data->kdRatio} does not match kills({$data->kills})/deaths({$data->deaths}) = {$expected}"];
        }

        return [];
    }

    /** @return list<string> */
    private function checkWeaponKills(CodMatchData $data): array
    {
        if ($data->weaponKills !== null && $data->weaponKills > $data->kills) {
            return ["Weapon kills ({$data->weaponKills}) cannot exceed total kills ({$data->kills})"];
        }

        return [];
    }

    /** @return list<string> */
    private function checkResultVsScores(CodMatchData $data): array
    {
        $victory = $data->result === CodResult::Victory;
        $teamHigher = $data->teamScore > $data->opponentScore;

        if ($victory !== $teamHigher) {
            return ["Result '{$data->result->value}' contradicts scores ({$data->teamScore} vs {$data->opponentScore})"];
        }

        return [];
    }

    /** @return list<string> */
    private function checkRanges(CodMatchData $data): array
    {
        $errors = [];

        if ($data->kills > self::MAX_KILLS) {
            $errors[] = "Kills ({$data->kills}) exceeds maximum of ".self::MAX_KILLS;
        }

        if ($data->deaths > self::MAX_DEATHS) {
            $errors[] = "Deaths ({$data->deaths}) exceeds maximum of ".self::MAX_DEATHS;
        }

        if ($data->score > self::MAX_SCORE) {
            $errors[] = "Score ({$data->score}) exceeds maximum of ".self::MAX_SCORE;
        }

        if ($data->damage > self::MAX_DAMAGE) {
            $errors[] = "Damage ({$data->damage}) exceeds maximum of ".self::MAX_DAMAGE;
        }

        if ($data->weaponAccuracy !== null && $data->weaponAccuracy > self::MAX_ACCURACY) {
            $errors[] = "Weapon accuracy ({$data->weaponAccuracy}) cannot exceed 100%";
        }

        return $errors;
    }
}
