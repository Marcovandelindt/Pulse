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

    public function store(Request $request): RedirectResponse|View
    {
        $request->validate([
            'screenshot' => ['required', 'image', 'max:10240'],
        ]);

        $path = $request->file('screenshot')->store('ocr-temp', 'local');
        $fullPath = storage_path('app/private/' . $path);

        try {
            $rawText  = $this->ocr->extractText($fullPath);
            $sessions = $this->ocr->parsePlayActivity($rawText);
        } finally {
            @unlink($fullPath);
        }

        // Store parsed sessions in session for the preview step
        session(['nintendo_ocr_sessions' => $sessions, 'nintendo_ocr_raw' => $rawText]);

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

        return redirect()->route('nintendo.sessions')
            ->with('success', "Imported {$count} sessions from screenshot.");
    }
}
