<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Tv;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\TvSeries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final class TvCastExclusionController extends Controller
{
    public function toggle(TvSeries $series, Person $person): RedirectResponse
    {
        $current = DB::table('tv_series_person')
            ->where('tv_series_id', $series->id)
            ->where('person_id', $person->id)
            ->where('department', 'Acting')
            ->value('excluded');

        DB::table('tv_series_person')
            ->where('tv_series_id', $series->id)
            ->where('person_id', $person->id)
            ->where('department', 'Acting')
            ->update(['excluded' => ! $current]);

        return redirect()->back();
    }
}
