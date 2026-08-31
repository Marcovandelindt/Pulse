<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ChangelogEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class CaptureChangelogEntry extends Command
{
    protected $signature = 'changelog:capture
                            {--hash= : Specific commit hash to capture}
                            {--all   : Backfill all commits from git history}';

    protected $description = 'Capture git commits as changelog entries';

    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->backfillAll();
        }

        $hash = $this->option('hash') ?? $this->getLatestCommitHash();

        if (! $hash) {
            return self::SUCCESS;
        }

        $this->captureCommit($hash);

        return self::SUCCESS;
    }

    private function backfillAll(): int
    {
        exec('git log --format=%H', $hashes, $code);

        if ($code !== 0 || empty($hashes)) {
            $this->error('Could not read git log.');

            return self::FAILURE;
        }

        $existing = ChangelogEntry::pluck('commit_hash')->flip();
        $toProcess = array_reverse(array_values(array_filter(
            $hashes,
            fn (string $h) => ! isset($existing[$h])
        )));

        if (empty($toProcess)) {
            $this->info('Nothing to backfill — all commits already captured.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($toProcess));
        $bar->start();

        foreach ($toProcess as $commitHash) {
            $this->captureCommit($commitHash);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill complete: '.count($toProcess).' entries added.');

        return self::SUCCESS;
    }

    private function captureCommit(string $hash): void
    {
        if (ChangelogEntry::where('commit_hash', $hash)->exists()) {
            return;
        }

        $commit = $this->getCommitInfo($hash);

        if (! $commit) {
            return;
        }

        [$type, $scope, $title] = $this->parseConventionalCommit($commit['subject']);

        ChangelogEntry::create([
            'commit_hash' => $hash,
            'type' => $type,
            'scope' => $scope,
            'title' => $title,
            'description' => $commit['body'],
            'files_changed' => $commit['files'],
            'stats' => $commit['stats'],
            'committed_at' => $commit['date'],
        ]);

        if (! $this->option('quiet')) {
            $this->line("  <fg=green>✓</> [{$type}] {$title}");
        }
    }

    private function getLatestCommitHash(): ?string
    {
        exec('git rev-parse HEAD', $output, $code);

        return $code === 0 && ! empty($output) ? trim($output[0]) : null;
    }

    private function getCommitInfo(string $hash): ?array
    {
        $escaped = escapeshellarg($hash);

        exec("git log -1 --format=%s {$escaped}", $subjectLines, $code);

        if ($code !== 0 || empty($subjectLines)) {
            return null;
        }

        exec("git log -1 --format=%b {$escaped}", $bodyLines);
        exec("git log -1 --format=%ai {$escaped}", $dateLines);
        exec("git diff-tree --no-commit-id -r --name-only {$escaped}", $fileLines);
        exec("git diff-tree --no-commit-id -r --stat {$escaped}", $statLines);

        $body = trim(implode("\n", array_filter($bodyLines)));
        $statSummary = ! empty($statLines) ? end($statLines) : '';

        return [
            'subject' => trim(implode(' ', $subjectLines)),
            'body' => $body ?: null,
            'date' => Carbon::parse(trim($dateLines[0] ?? 'now')),
            'files' => array_values(array_filter($fileLines)),
            'stats' => $this->parseStatLine((string) $statSummary),
        ];
    }

    private function parseConventionalCommit(string $subject): array
    {
        if (preg_match('/^(\w+)(?:\(([^)]+)\))?!?:\s*(.+)$/', $subject, $matches)) {
            return [
                strtolower($matches[1]),
                $matches[2] ?: null,
                $matches[3],
            ];
        }

        return ['chore', null, $subject];
    }

    private function parseStatLine(string $line): array
    {
        preg_match('/(\d+) files? changed/', $line, $filesMatch);
        preg_match('/(\d+) insertions?/', $line, $insertionsMatch);
        preg_match('/(\d+) deletions?/', $line, $deletionsMatch);

        return [
            'files' => (int) ($filesMatch[1] ?? 0),
            'insertions' => (int) ($insertionsMatch[1] ?? 0),
            'deletions' => (int) ($deletionsMatch[1] ?? 0),
        ];
    }
}
