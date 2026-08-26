<?php

declare(strict_types=1);

namespace App\Http\Controllers\Insights;

use App\Http\Controllers\Controller;
use App\Models\Insight;
use App\Models\Pattern;
use Illuminate\Http\RedirectResponse;

final class InsightPatternController extends Controller
{
    public function store(Insight $insight, Pattern $pattern): RedirectResponse
    {
        $insight->patterns()->syncWithoutDetaching([$pattern->id]);

        return back()->with('success', 'Pattern linked.');
    }

    public function destroy(Insight $insight, Pattern $pattern): RedirectResponse
    {
        $insight->patterns()->detach($pattern->id);

        return back()->with('success', 'Pattern unlinked.');
    }
}
