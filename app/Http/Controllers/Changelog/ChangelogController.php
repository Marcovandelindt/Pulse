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
        $entries = ChangelogEntry::orderByDesc('committed_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (ChangelogEntry $entry) => $entry->committed_at->format('Y-m-d'));

        return view('pages.changelog.index', compact('entries'));
    }
}
