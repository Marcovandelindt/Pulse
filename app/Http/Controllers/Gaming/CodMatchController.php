<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Enums\CodMatchStatus;
use App\Http\Controllers\Controller;
use App\Models\CodMatch;
use Illuminate\View\View;

final class CodMatchController extends Controller
{
    public function index(): View
    {
        $matches = CodMatch::with(['map', 'mode', 'topWeapon'])
            ->where('status', CodMatchStatus::Confirmed)
            ->orderByDesc('estimated_date')
            ->orderByDesc('id')
            ->paginate(25);

        $pendingCount = CodMatch::where('status', CodMatchStatus::PendingReview)->count();

        return view('pages.playstation.cod.index', compact('matches', 'pendingCount'));
    }
}
