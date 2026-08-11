<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use App\Http\Requests\Health\StoreStepGoalRequest;
use App\Models\StepGoal;
use Illuminate\Http\RedirectResponse;

final class StepGoalController extends Controller
{
    public function store(StoreStepGoalRequest $request): RedirectResponse
    {
        StepGoal::create([
            'steps' => (int) $request->validated('steps'),
            'effective_from' => $request->validated('effective_from'),
        ]);

        return redirect()->route('health.index')->with('success', 'Step goal updated.');
    }
}
