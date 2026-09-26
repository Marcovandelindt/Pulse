<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\Nintendo\ImportOcrSessions;
use App\Http\Controllers\Controller;
use App\Services\Nintendo\OcrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NintendoImportController extends Controller
{
    public function __construct(
        private readonly OcrService $ocr,
    ) {}

    public function create(): View
    {
        return view('pages.nintendo.import', [
            'tesseractAvailable' => $this->ocr->isAvailable(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'screenshots'   => ['required', 'array', 'min:1', 'max:10'],
            'screenshots.*' => ['image', 'max:10240'],
        ]);

        $sessions = [];
        $rawParts = [];

        foreach ($request->file('screenshots') as $index => $file) {
            $path     = $file->store('ocr-temp', 'local');
            $fullPath = storage_path('app/private/' . $path);

            try {
                $rawText    = $this->ocr->extractText($fullPath);
                $rawParts[] = '--- Image ' . ($index + 1) . " ---\n" . $rawText;

                foreach ($this->ocr->parsePlayActivity($rawText) as $session) {
                    $key            = $session['game'] . '|' . $session['date'];
                    $sessions[$key] = $session;
                }
            } finally {
                @unlink($fullPath);
            }
        }

        session([
            'nintendo_ocr_sessions' => array_values($sessions),
            'nintendo_ocr_raw'      => implode("\n\n", $rawParts),
        ]);

        return redirect()->route('nintendo.import.preview');
    }

    public function preview(): View
    {
        $sessions = session('nintendo_ocr_sessions', []);
        $rawText  = session('nintendo_ocr_raw', '');

        return view('pages.nintendo.import-preview', compact('sessions', 'rawText'));
    }

    public function confirm(Request $request, ImportOcrSessions $action): RedirectResponse
    {
        $request->validate([
            'sessions'              => ['required', 'array'],
            'sessions.*.game'       => ['required', 'string', 'max:255'],
            'sessions.*.date'       => ['required', 'date'],
            'sessions.*.minutes'    => ['required', 'integer', 'min:1', 'max:1440'],
            'sessions.*.import'     => ['nullable', 'boolean'],
        ]);

        $toImport = array_filter(
            $request->input('sessions'),
            fn (array $s) => ! empty($s['import']),
        );

        $count = $action->handle(array_values($toImport));

        session()->forget(['nintendo_ocr_sessions', 'nintendo_ocr_raw']);

        return redirect()->route('nintendo.import')
            ->with('success', "Imported {$count} sessions.");
    }
}
