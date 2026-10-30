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

        Return ONLY a valid JSON object with exactly these fields (no extra text, no markdown):
        {
          "map": "string — map name",
          "mode": "string — game mode name",
          "result": "victory or defeat",
          "team_score": integer,
          "opponent_score": integer,
          "score": integer,
          "kills": integer,
          "deaths": integer,
          "kd_ratio": number,
          "objective_score": integer or null,
          "damage": integer,
          "weapon_name": "string or null",
          "weapon_category": "string or null",
          "weapon_accuracy": number or null,
          "weapon_kills": integer or null,
          "relative_time": "string — e.g. '2 hours ago'"
        }
        PROMPT;
    }
}
