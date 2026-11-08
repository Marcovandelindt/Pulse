<?php

declare(strict_types=1);

namespace App\Http\Controllers\Waze;

use App\Http\Controllers\Controller;
use App\Models\WazeDrive;
use Illuminate\View\View;

final class WazeDriveController extends Controller
{
    public function index(): View
    {
        $drives = WazeDrive::orderByDesc('driven_at')->paginate(25);

        return view('pages.waze.index', compact('drives'));
    }
}
