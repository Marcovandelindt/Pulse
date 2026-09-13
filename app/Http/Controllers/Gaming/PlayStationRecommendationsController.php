<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Queries\Gaming\PlayStationRecommendationsQuery;
use Illuminate\View\View;

final class PlayStationRecommendationsController extends Controller
{
    public function index(PlayStationRecommendationsQuery $query): View
    {
        return view('pages.playstation.recommendations', $query->handle());
    }
}
