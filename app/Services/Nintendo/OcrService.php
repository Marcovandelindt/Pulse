<?php

declare(strict_types=1);

namespace App\Services\Nintendo;

use Illuminate\Support\Facades\Process;
use RuntimeException;

final class OcrService
{
    public function isAvailable(): bool
    {
        return Process::run([$this->binary(), '--version'])->successful();
    }

    public function extractText(string $imagePath): string
    {
        $result = Process::run([
            $this->binary(),
            $imagePath,
            'stdout',
            '--psm', '6',
            '-l', 'eng',
        ]);

        if (! $result->successful()) {
            throw new RuntimeException('Tesseract failed: ' . $result->errorOutput());
        }

        return trim($result->output());
    }

    /**
     * Parse OCR text from the Nintendo Store Play Activity screen.
     *
     * Actual format (Dutch):
     *   Pokémon Shield
     *   30-08-2026  15 min.
     *   26-08-2026  Korte tijd
     *   01-08-2026  1u15 min.
     *
     * Game name appears once, all date lines below it belong to that game.
     * Multiple games can appear in one screenshot.
     *
     * @return array<int, array{game: string, date: string|null, minutes: int, needs_review: bool}>
     */
    public function parsePlayActivity(string $text): array
    {
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $text)),
            fn (string $l) => $l !== '',
        ));

        $sessions = [];
        $currentGame = null;
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            $line = $lines[$i];

            // Session line: DD-MM-YYYY followed by duration on the same line
            if (preg_match('/^(\d{2}-\d{2}-\d{4})\s+(.+)$/u', $line, $m)) {
                $date        = $this->parseDutchDate($m[1]);
                $rest        = trim($m[2]);
                $minutes     = $this->parseDuration($rest);
                $needsReview = false;

                if ($minutes === null && $this->isShortTime($rest)) {
                    $minutes     = 1;
                    $needsReview = true;
                }

                if ($minutes !== null) {
                    $sessions[] = [
                        'game'         => $currentGame ?? '',
                        'date'         => $date,
                        'minutes'      => $minutes,
                        'needs_review' => $needsReview,
                    ];
                }

                continue;
            }

            // Session line split across two lines: date alone, duration on next line
            if (preg_match('/^(\d{2}-\d{2}-\d{4})$/', $line, $m)) {
                $next = $lines[$i + 1] ?? null;

                if ($next !== null) {
                    $date        = $this->parseDutchDate($m[1]);
                    $minutes     = $this->parseDuration($next);
                    $needsReview = false;

                    if ($minutes === null && $this->isShortTime($next)) {
                        $minutes     = 1;
                        $needsReview = true;
                    }

                    if ($minutes !== null) {
                        $sessions[] = [
                            'game'         => $currentGame ?? '',
                            'date'         => $date,
                            'minutes'      => $minutes,
                            'needs_review' => $needsReview,
                        ];
                        $i++; // skip the duration line
                        continue;
                    }
                }
            }

            // Skip status-bar noise and single characters
            if ($this->isUiNoise($line)) {
                continue;
            }

            // Anything else is treated as a game name; strip Nintendo's truncation ellipsis
            $currentGame = trim((string) preg_replace('/\.{3}$|…$/', '', $line));
        }

        return $sessions;
    }

    private function parseDutchDate(string $date): string
    {
        [$day, $month, $year] = explode('-', $date);

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private function parseDuration(string $text): ?int
    {
        // Fix common OCR error: letter O mistaken for digit 0 in numeric contexts
        $text = (string) preg_replace('/(\d)O/i', '${1}0', $text);
        $text = (string) preg_replace('/O(\d)/i', '0${1}', $text);

        $total = 0;
        $found = false;

        // Dutch hours+minutes: "1u15", "1u 30", "1u00"
        if (preg_match('/(\d+)\s*u\s*(\d+)/i', $text, $m)) {
            $total += (int) $m[1] * 60 + (int) $m[2];
            $found  = true;
        }
        // Dutch hours only: "2u" (no minutes following)
        elseif (preg_match('/(\d+)\s*u(?:\s|$|\.)/i', $text, $m)) {
            $total += (int) $m[1] * 60;
            $found  = true;
        }

        // Minutes: "15 min.", "30min"
        if (preg_match('/(\d+)\s*min/i', $text, $m)) {
            $total += (int) $m[1];
            $found  = true;
        }

        return $found ? max(1, $total) : null;
    }

    private function isShortTime(string $line): bool
    {
        return (bool) preg_match('/korte\s+tijd|short\s+time/i', $line);
    }

    private function isUiNoise(string $line): bool
    {
        // Very short strings
        if (mb_strlen($line) <= 2) {
            return true;
        }

        // iPhone status bar: "16:49 wi! > 6D", "9:41", etc.
        if (preg_match('/^\d{1,2}:\d{2}/', $line)) {
            return true;
        }

        // Standalone symbols: ©, ®, >, arrows
        if (preg_match('/^[©®™><\-–—|]+$/', $line)) {
            return true;
        }

        return false;
    }

    private function binary(): string
    {
        return env('TESSERACT_PATH', 'tesseract');
    }
}
