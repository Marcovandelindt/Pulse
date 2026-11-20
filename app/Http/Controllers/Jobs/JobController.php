<?php

declare(strict_types=1);

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jobs\StoreJobRequest;
use App\Http\Requests\Jobs\UpdateJobRequest;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class JobController extends Controller
{
    public function index(): View
    {
        $jobs = Job::orderByDesc('started_at')->get();

        return view('pages.jobs.index', compact('jobs'));
    }

    public function create(): View
    {
        return view('pages.jobs.create', ['allDays' => Job::allDays()]);
    }

    public function store(StoreJobRequest $request): RedirectResponse
    {
        Job::create($request->validated());

        return redirect()->route('jobs.index')->with('success', 'Job saved.');
    }

    public function edit(Job $job): View
    {
        return view('pages.jobs.edit', ['job' => $job, 'allDays' => Job::allDays()]);
    }

    public function update(UpdateJobRequest $request, Job $job): RedirectResponse
    {
        $job->update($request->validated());

        return redirect()->route('jobs.index')->with('success', 'Job updated.');
    }

    public function destroy(Job $job): RedirectResponse
    {
        $job->delete();

        return redirect()->route('jobs.index')->with('success', 'Job removed.');
    }
}
