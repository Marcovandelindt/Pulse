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
        $result = $action->handle($request->file('file'));

        return redirect()->route('waze.index')
            ->with('success', "{$result->imported} drives imported, {$result->skipped} skipped.");
    }
}
