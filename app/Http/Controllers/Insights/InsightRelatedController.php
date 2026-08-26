<?php

declare(strict_types=1);

namespace App\Http\Controllers\Insights;

use App\Http\Controllers\Controller;
use App\Models\Insight;
use Illuminate\Http\RedirectResponse;

final class InsightRelatedController extends Controller
{
    public function store(Insight $insight, Insight $related): RedirectResponse
    {
        if ($insight->id === $related->id) {
            return back()->with('error', 'Cannot link an insight to itself.');
        }

        $alreadyLinked = $insight->related()->where('related_insight_id', $related->id)->exists()
            || $insight->relatedBy()->where('insight_id', $related->id)->exists();

        if (! $alreadyLinked) {
            $insight->related()->attach($related->id);
        }

        return back()->with('success', 'Related insight linked.');
    }

    public function destroy(Insight $insight, Insight $related): RedirectResponse
    {
        $insight->related()->detach($related->id);
        $insight->relatedBy()->detach($related->id);

        return back()->with('success', 'Related insight unlinked.');
    }
}
