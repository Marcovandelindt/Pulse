<?php

declare(strict_types=1);

namespace App\Http\Controllers\Insights;

use App\Actions\Insights\CreateInsight;
use App\Actions\Insights\DeleteInsight;
use App\Actions\Insights\InsightData;
use App\Actions\Insights\UpdateInsight;
use App\Http\Controllers\Controller;
use App\Http\Requests\Insights\StoreInsightRequest;
use App\Http\Requests\Insights\UpdateInsightRequest;
use App\Models\Insight;
use App\Models\Pattern;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class InsightController extends Controller
{
    public function index(Request $request): View
    {
        $search = (string) $request->query('q', '');

        $query = Insight::query()->with('patterns')->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $pinned = $search === ''
            ? Insight::query()->where('is_pinned', true)->with('patterns')->latest()->get()
            : collect();

        if ($pinned->isNotEmpty()) {
            $query->whereNotIn('id', $pinned->pluck('id'));
        }

        $insights      = $query->paginate(20)->withQueryString();
        $totalCount    = Insight::query()->count();
        $pinnedCount   = Insight::query()->where('is_pinned', true)->count();
        $quickRefCount = Insight::query()->where('is_quick_ref', true)->count();

        return view('pages.insights.index', compact(
            'search', 'pinned', 'insights', 'totalCount', 'pinnedCount', 'quickRefCount',
        ));
    }

    public function create(): View
    {
        return view('pages.insights.create');
    }

    public function store(StoreInsightRequest $request, CreateInsight $action): RedirectResponse
    {
        $insight = $action->handle(InsightData::fromRequest($request));

        return redirect()->route('insights.show', $insight)->with('success', 'Insight saved.');
    }

    public function show(Insight $insight): View
    {
        $insight->load(['patterns', 'related', 'relatedBy']);
        $allRelated = $insight->allRelated();

        $linkedPatternIds = $insight->patterns->pluck('id');
        $availablePatterns = Pattern::query()
            ->whereNotIn('id', $linkedPatternIds)
            ->orderBy('title')
            ->get();

        $linkedInsightIds = $allRelated->pluck('id')->push($insight->id);
        $availableInsights = Insight::query()
            ->whereNotIn('id', $linkedInsightIds)
            ->orderByDesc('created_at')
            ->get();

        return view('pages.insights.show', compact(
            'insight', 'allRelated', 'availablePatterns', 'availableInsights',
        ));
    }

    public function edit(Insight $insight): View
    {
        return view('pages.insights.edit', compact('insight'));
    }

    public function update(UpdateInsightRequest $request, Insight $insight, UpdateInsight $action): RedirectResponse
    {
        $action->handle($insight, InsightData::fromRequest($request));

        return redirect()->route('insights.show', $insight)->with('success', 'Insight updated.');
    }

    public function destroy(Insight $insight, DeleteInsight $action): RedirectResponse
    {
        $action->handle($insight);

        return redirect()->route('insights.index')->with('success', 'Insight deleted.');
    }

    public function togglePin(Insight $insight): RedirectResponse
    {
        $insight->update(['is_pinned' => ! $insight->is_pinned]);

        $label = $insight->is_pinned ? 'Insight pinned.' : 'Insight unpinned.';

        return back()->with('success', $label);
    }
}
