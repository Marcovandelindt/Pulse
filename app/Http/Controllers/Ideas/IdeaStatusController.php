<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ideas;

use App\Enums\IdeaStatus;
use App\Http\Controllers\Controller;
use App\Models\Idea;
use Illuminate\Http\RedirectResponse;

final class IdeaStatusController extends Controller
{
    public function update(Idea $idea): RedirectResponse
    {
        $idea->update(['status' => $idea->status->next()]);

        return redirect()->route('ideas.index');
    }
}
