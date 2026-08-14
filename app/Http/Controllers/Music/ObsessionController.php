<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Track;
use Illuminate\Http\RedirectResponse;

final class ObsessionController extends Controller
{
    public function toggle(Track $track): RedirectResponse
    {
        $wasObsession = $track->is_obsession;

        $track->update([
            'is_obsession' => ! $wasObsession,
            'obsession_since' => $wasObsession ? null : now(),
        ]);

        return back()->with('success', $wasObsession ? 'Removed from obsessions.' : 'Added to obsessions.');
    }
}
