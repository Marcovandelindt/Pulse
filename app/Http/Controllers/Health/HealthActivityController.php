<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class HealthActivityController extends Controller
{
    public function index(): View
    {
        return view('pages.health.activity');
    }
}
