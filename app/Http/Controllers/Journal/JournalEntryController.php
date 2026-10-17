<?php

declare(strict_types=1);

namespace App\Http\Controllers\Journal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Journal\StoreJournalEntryRequest;
use App\Http\Requests\Journal\UpdateJournalEntryRequest;
use App\Models\JournalEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class JournalEntryController extends Controller
{
    public function index(): View
    {
        $entries = JournalEntry::orderByDesc('date')->paginate(20);

        $streak  = $this->currentStreak();
        $total   = JournalEntry::count();

        return view('pages.journal.index', compact('entries', 'streak', 'total'));
    }

    public function create(): View
    {
        $suggestedDate = JournalEntry::where('date', today())->exists()
            ? null
            : today()->format('Y-m-d');

        return view('pages.journal.create', compact('suggestedDate'));
    }

    public function store(StoreJournalEntryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['tags'] = $this->parseTags($data['tags'] ?? null);

        $entry = JournalEntry::create($data);

        return redirect()->route('journal.show', $entry)->with('success', 'Entry saved.');
    }

    public function show(JournalEntry $journalEntry): View
    {
        $prev = JournalEntry::where('date', '<', $journalEntry->date)->orderByDesc('date')->first();
        $next = JournalEntry::where('date', '>', $journalEntry->date)->orderBy('date')->first();

        return view('pages.journal.show', compact('journalEntry', 'prev', 'next'));
    }

    public function edit(JournalEntry $journalEntry): View
    {
        return view('pages.journal.edit', compact('journalEntry'));
    }

    public function update(UpdateJournalEntryRequest $request, JournalEntry $journalEntry): RedirectResponse
    {
        $data = $request->validated();
        $data['tags'] = $this->parseTags($data['tags'] ?? null);

        $journalEntry->update($data);

        return redirect()->route('journal.show', $journalEntry)->with('success', 'Entry updated.');
    }

    public function destroy(JournalEntry $journalEntry): RedirectResponse
    {
        $journalEntry->delete();

        return redirect()->route('journal.index')->with('success', 'Entry deleted.');
    }

    private function currentStreak(): int
    {
        $streak = 0;
        $check  = today();

        while (JournalEntry::where('date', $check)->exists()) {
            $streak++;
            $check = $check->subDay();
        }

        return $streak;
    }

    private function parseTags(?string $tags): ?array
    {
        if (blank($tags)) {
            return null;
        }

        return collect(explode(',', $tags))
            ->map(fn (string $t) => trim($t))
            ->filter()
            ->values()
            ->all();
    }
}
