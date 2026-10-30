<?php

declare(strict_types=1);

namespace App\Actions\PlayStation\Cod;

use App\Enums\CodMatchStatus;
use App\Models\CodMap;
use App\Models\CodMatch;
use App\Models\CodMode;
use App\Models\CodScreenshot;
use App\Models\CodWeapon;
use App\Services\PlayStation\CodVisionService;
use Carbon\Carbon;

final class ProcessCodScreenshot
{
    // Near-duplicate threshold: if this many of 11 fingerprint fields match,
    // flag for review instead of auto-confirming.
    private const int NEAR_MATCH_THRESHOLD = 9;

    public function __construct(
        private readonly CodVisionService $vision,
        private readonly ValidateCodMatch $validator,
        private readonly BuildCodFingerprint $fingerprinter,
    ) {}

    public function handle(string $storedPath, string $filename, string $fileHash, Carbon $takenAt): ProcessResult
    {
        $existing = CodScreenshot::where('file_hash', $fileHash)->first();

        if ($existing !== null) {
            return new ProcessResult(
                status: ProcessResultStatus::ScreenshotDuplicate,
                match: $existing->matches()->first(),
                screenshot: $existing,
            );
        }

        $screenshot = CodScreenshot::create([
            'filename' => $filename,
            'file_hash' => $fileHash,
            'taken_at' => $takenAt,
        ]);

        $raw = $this->vision->extract($storedPath);
        $data = CodMatchData::fromArray($raw, $takenAt);

        $validation = $this->validator->handle($data);
        $fields = $this->fingerprinter->fields($data);
        $fingerprint = $this->fingerprinter->handle($data);

        $exactMatch = CodMatch::where('fingerprint', $fingerprint)->first();

        if ($exactMatch !== null) {
            $exactMatch->screenshots()->attach($screenshot);

            return new ProcessResult(
                status: ProcessResultStatus::MatchDuplicate,
                match: $exactMatch,
                screenshot: $screenshot,
            );
        }

        $status = $this->resolveStatus($validation->passed, $fields);

        $match = $this->storeMatch($data, $fingerprint, $status);
        $match->screenshots()->attach($screenshot);

        return new ProcessResult(
            status: $status === CodMatchStatus::Confirmed
                ? ProcessResultStatus::Confirmed
                : ProcessResultStatus::PendingReview,
            match: $match,
            screenshot: $screenshot,
            validationErrors: $validation->errors,
        );
    }

    /** @param array<string, string> $fields */
    private function resolveStatus(bool $validationPassed, array $fields): CodMatchStatus
    {
        if (! $validationPassed) {
            return CodMatchStatus::PendingReview;
        }

        if ($this->hasNearMatch($fields)) {
            return CodMatchStatus::PendingReview;
        }

        return CodMatchStatus::Confirmed;
    }

    /** @param array<string, string> $fields */
    private function hasNearMatch(array $fields): bool
    {
        return CodMatch::query()
            ->with(['map', 'mode', 'topWeapon'])
            ->where('status', CodMatchStatus::Confirmed)
            ->get()
            ->contains(function (CodMatch $match) use ($fields): bool {
                $existingFields = $this->fingerprinter->fieldsFromMatch($match);

                return $this->fingerprinter->similarity($fields, $existingFields) >= self::NEAR_MATCH_THRESHOLD;
            });
    }

    private function storeMatch(CodMatchData $data, string $fingerprint, CodMatchStatus $status): CodMatch
    {
        $map = CodMap::firstOrCreate(['name' => $data->map]);
        $mode = CodMode::firstOrCreate(['name' => $data->mode]);

        $weapon = null;
        if ($data->weaponName !== null) {
            $weapon = CodWeapon::firstOrCreate(
                ['name' => $data->weaponName],
                ['category' => $data->weaponCategory ?? 'Unknown'],
            );
        }

        return CodMatch::create([
            'cod_map_id' => $map->id,
            'cod_mode_id' => $mode->id,
            'result' => $data->result,
            'team_score' => $data->teamScore,
            'opponent_score' => $data->opponentScore,
            'score' => $data->score,
            'kills' => $data->kills,
            'deaths' => $data->deaths,
            'kd_ratio' => $data->kdRatio,
            'objective_score' => $data->objectiveScore,
            'damage' => $data->damage,
            'top_weapon_id' => $weapon?->id,
            'weapon_accuracy' => $data->weaponAccuracy,
            'weapon_kills' => $data->weaponKills,
            'estimated_date' => $data->estimatedDate,
            'fingerprint' => $fingerprint,
            'status' => $status,
        ]);
    }
}
