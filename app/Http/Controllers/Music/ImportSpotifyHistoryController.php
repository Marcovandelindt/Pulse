<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSpotifyImport;
use App\Models\SpotifyImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class ImportSpotifyHistoryController extends Controller
{
    public function index(): View
    {
        $imports = SpotifyImport::orderByDesc('created_at')->get();

        return view('pages.music.import', compact('imports'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'max:102400'],
        ]);

        foreach ($request->file('files') as $file) {
            $path = $file->store('imports/spotify');

            $import = SpotifyImport::create([
                'filename'  => $file->getClientOriginalName(),
                'file_path' => $path,
            ]);

            ProcessSpotifyImport::dispatch($import);
        }

        return redirect()->route('music.import.index')
            ->with('success', count($request->file('files')).' file(s) queued for import.');
    }

    public function progress(SpotifyImport $import): JsonResponse
    {
        return response()->json([
            'status'        => $import->status,
            'total_entries' => $import->total_entries,
            'processed'     => $import->processed,
            'synced'        => $import->synced,
            'skipped'       => $import->skipped,
            'percentage'    => $import->progress_percentage,
            'error'         => $import->error,
        ]);
    }
}
