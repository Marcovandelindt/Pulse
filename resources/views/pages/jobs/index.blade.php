<x-layouts.app title="Jobs">

    <x-layout.page-header title="Jobs">
        <x-slot:actions>
            <a href="{{ route('jobs.create') }}" class="btn btn--primary btn--sm">+ Add job</a>
        </x-slot:actions>
    </x-layout.page-header>

    @if ($jobs->isEmpty())
        <x-ui.empty-state title="No jobs yet" description="Add your first job to start tracking your work history.">
            <x-slot:action>
                <a href="{{ route('jobs.create') }}" class="btn btn--primary">+ Add job</a>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <div class="jobs-timeline">
            @foreach ($jobs as $job)
                <div class="job-card {{ $job->isActive() ? 'job-card--active' : '' }}">
                    <div class="job-card__indicator">
                        <div class="job-card__dot"></div>
                        @if (! $loop->last)
                            <div class="job-card__line"></div>
                        @endif
                    </div>

                    <div class="job-card__body">
                        <div class="job-card__header">
                            <div class="job-card__meta">
                                <div class="job-card__employer">
                                    {{ $job->employer }}
                                    @if ($job->isActive())
                                        <span class="job-card__badge">Current</span>
                                    @endif
                                </div>
                                @if ($job->title)
                                    <div class="job-card__title">{{ $job->title }}</div>
                                @endif
                            </div>
                            <div class="job-card__actions">
                                <a href="{{ route('jobs.edit', $job) }}" class="btn btn--secondary btn--sm">Edit</a>
                                <form method="POST" action="{{ route('jobs.destroy', $job) }}" onsubmit="return confirm('Remove this job?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                                </form>
                            </div>
                        </div>

                        <div class="job-card__details">
                            <div class="job-card__period">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                                </svg>
                                <span>
                                    {{ $job->started_at->format('M Y') }} –
                                    {{ $job->ended_at ? $job->ended_at->format('M Y') : 'Present' }}
                                </span>
                                <span class="job-card__duration">{{ $job->durationLabel() }}</span>
                            </div>

                            <div class="job-card__days">
                                @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                    <span class="job-card__day {{ in_array($day, $job->work_days) ? 'job-card__day--active' : '' }}">
                                        {{ substr(ucfirst($day), 0, 2) }}
                                    </span>
                                @endforeach

                                @if ($job->work_start_time && $job->work_end_time)
                                    <span class="job-card__hours">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        {{ $job->work_start_time }} – {{ $job->work_end_time }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if ($job->notes)
                            <p class="job-card__notes">{{ $job->notes }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</x-layouts.app>
