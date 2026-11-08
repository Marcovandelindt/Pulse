<?php

declare(strict_types=1);

namespace App\Http\Controllers\Waze;

use App\Actions\Waze\ImportWazeDrivesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Waze\ImportWazeDrivesRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class WazeImportController extends Controller
{
    public function index(): View
    {
        return view('pages.waze.import');
    }

    public function store(ImportWazeDrivesRequest $request, ImportWazeDrivesAction $action): RedirectResponse
    {
        try {
            $result = $action->handle($request->file('file'), $request->string('password')->value() ?: null);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors(['password' => $e->getMessage()]);
        }

        $parts = ["{$result->imported} imported", "{$result->skipped} skipped"];

        if ($result->enriched > 0) {
            $parts[] = "{$result->enriched} enriched with GPS origin";
        }

        return redirect()->route('waze.index')
            ->with('success', implode(', ', $parts).'.');
    }
}
