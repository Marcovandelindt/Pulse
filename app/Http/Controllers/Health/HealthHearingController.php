<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class HealthHearingController extends Controller
{
    public function index(): View
    {
        return view('pages.health.hearing');
    }
}
