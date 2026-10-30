<?php

declare(strict_types=1);

namespace App\Services\PlayStation;

use App\Services\Ollama\OllamaService;

final class CodVisionService
{
    public function __construct(
        private readonly OllamaService $ollama,
    ) {}

    /**
     * Extract match data from a PS5 Combat Record screenshot.
     *
     * @return array<string, mixed>
     */
    public function extract(string $imagePath): array
    {
        return $this->ollama->analyzeImage(
            imagePath: $imagePath,
            prompt: $this->prompt(),
            schema: $this->schema(),
        );
    }

    private function prompt(): string
    {
        return <<<'PROMPT'
        You are reading a Call of Duty: Black Ops 6 "Recent Matches" screen captured from a PS5.

        The screen has two parts:
        - LEFT: a list of matches (mode, map, result, team scores, relative time)
        - RIGHT: a detail panel for the selected (highlighted) match

        Extract ALL fields from the SELECTED match only. Use the detail panel on the right as the primary source.
        Cross-reference with the highlighted row on the left for mode, map, result and scores.

        Return only the JSON object — no explanation, no markdown.
        PROMPT;
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'map' => ['type' => 'string'],
                'mode' => ['type' => 'string'],
                'result' => ['type' => 'string', 'enum' => ['victory', 'defeat']],
                'team_score' => ['type' => 'integer'],
                'opponent_score' => ['type' => 'integer'],
                'score' => ['type' => 'integer'],
                'kills' => ['type' => 'integer'],
                'deaths' => ['type' => 'integer'],
                'kd_ratio' => ['type' => 'number'],
                'objective_score' => ['type' => ['integer', 'null']],
                'damage' => ['type' => 'integer'],
                'weapon_name' => ['type' => ['string', 'null']],
                'weapon_category' => ['type' => ['string', 'null']],
                'weapon_accuracy' => ['type' => ['number', 'null']],
                'weapon_kills' => ['type' => ['integer', 'null']],
                'relative_time' => ['type' => 'string'],
            ],
            'required' => [
                'map', 'mode', 'result', 'team_score', 'opponent_score',
                'score', 'kills', 'deaths', 'kd_ratio', 'damage', 'relative_time',
            ],
        ];
    }
}
