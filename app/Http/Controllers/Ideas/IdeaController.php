<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ideas;

use App\Enums\IdeaPriority;
use App\Enums\IdeaStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ideas\StoreIdeaRequest;
use App\Http\Requests\Ideas\UpdateIdeaRequest;
use App\Models\Idea;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class IdeaController extends Controller
{
    public function index(): View
    {
        $status = request('status');
        $module = request('module');

        $ideas = Idea::query()
            ->when($status, fn ($q) => $q->byStatus(IdeaStatus::from($status)))
            ->when($module, fn ($q) => $q->byModule($module))
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'planned' THEN 1 WHEN 'idea' THEN 2 WHEN 'done' THEN 3 END")
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 WHEN 'low' THEN 2 END")
            ->orderBy('created_at', 'desc')
            ->get();

        $modules = Idea::query()->whereNotNull('module')->distinct()->pluck('module')->sort()->values();

        return view('pages.ideas.index', [
            'ideas'      => $ideas,
            'modules'    => $modules,
            'statuses'   => IdeaStatus::cases(),
            'priorities' => IdeaPriority::cases(),
            'filterStatus' => $status,
            'filterModule' => $module,
            'totalCount'   => Idea::count(),
            'doneCount'    => Idea::byStatus(IdeaStatus::Done)->count(),
            'pendingCount' => Idea::pending()->count(),
        ]);
    }

    public function store(StoreIdeaRequest $request): RedirectResponse
    {
        Idea::create($request->validated());

        return redirect()->route('ideas.index')->with('success', 'Idea added.');
    }

    public function update(UpdateIdeaRequest $request, Idea $idea): RedirectResponse
    {
        $idea->update($request->validated());

        return redirect()->route('ideas.index')->with('success', 'Idea updated.');
    }

    public function destroy(Idea $idea): RedirectResponse
    {
        $idea->delete();

        return redirect()->route('ideas.index')->with('success', 'Idea deleted.');
    }
}
