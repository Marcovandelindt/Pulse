<?php

declare(strict_types=1);

namespace App\Http\Controllers\Changelog;

use App\Http\Controllers\Controller;
use App\Models\ChangelogEntry;
use Illuminate\View\View;

final class ChangelogController extends Controller
{
    public function index(): View
    {
        $validHashes = $this->reachableCommitHashes();

        $entries = ChangelogEntry::when(
                $validHashes !== null,
                fn ($q) => $q->whereIn('commit_hash', $validHashes)
            )
            ->orderByDesc('committed_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (ChangelogEntry $entry) => $entry->committed_at->format('Y-m-d'));

        return view('pages.changelog.index', compact('entries'));
    }

    private function reachableCommitHashes(): ?array
    {
        $output = [];
        exec('git -C ' . escapeshellarg(base_path()) . ' log --pretty=format:%H', $output, $exitCode);

        if ($exitCode !== 0 || empty($output)) {
            return null;
        }

        return array_values(array_filter($output));
    }
}
