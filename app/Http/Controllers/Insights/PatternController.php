<?php

declare(strict_types=1);

namespace App\Http\Controllers\Insights;

use App\Actions\Insights\CreatePattern;
use App\Actions\Insights\DeletePattern;
use App\Actions\Insights\PatternData;
use App\Actions\Insights\UpdatePattern;
use App\Http\Controllers\Controller;
use App\Http\Requests\Insights\StorePatternRequest;
use App\Http\Requests\Insights\UpdatePatternRequest;
use App\Models\Pattern;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PatternController extends Controller
{
    public function index(): View
    {
        $patterns = Pattern::query()
            ->withCount('insights')
            ->orderByDesc('insights_count')
            ->orderBy('title')
            ->get();

        return view('pages.patterns.index', compact('patterns'));
    }

    public function store(StorePatternRequest $request, CreatePattern $action): RedirectResponse
    {
        $pattern = $action->handle(PatternData::fromRequest($request));

        return redirect()->route('patterns.show', $pattern)->with('success', 'Pattern created.');
    }

    public function show(Pattern $pattern): View
    {
        $pattern->load('insights');

        return view('pages.patterns.show', compact('pattern'));
    }

    public function edit(Pattern $pattern): View
    {
        return view('pages.patterns.edit', compact('pattern'));
    }

    public function update(UpdatePatternRequest $request, Pattern $pattern, UpdatePattern $action): RedirectResponse
    {
        $action->handle($pattern, PatternData::fromRequest($request));

        return redirect()->route('patterns.show', $pattern)->with('success', 'Pattern updated.');
    }

    public function destroy(Pattern $pattern, DeletePattern $action): RedirectResponse
    {
        $action->handle($pattern);

        return redirect()->route('patterns.index')->with('success', 'Pattern deleted.');
    }
}
