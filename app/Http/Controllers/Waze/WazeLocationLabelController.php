<?php

declare(strict_types=1);

namespace App\Http\Controllers\Waze;

use App\Http\Controllers\Controller;
use App\Models\WazeLocationLabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class WazeLocationLabelController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'match_name' => ['required', 'string', 'max:100'],
        ]);

        WazeLocationLabel::create($validated);

        return redirect()->route('waze.index');
    }

    public function destroy(WazeLocationLabel $label): RedirectResponse
    {
        $label->delete();

        return redirect()->route('waze.index');
    }
}
