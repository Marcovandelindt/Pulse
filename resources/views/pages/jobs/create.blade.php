<x-layouts.app title="Add Job">

    <x-layout.page-header title="Add Job">
        <x-slot:actions>
            <a href="{{ route('jobs.index') }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card>
        <form method="POST" action="{{ route('jobs.store') }}">
            @csrf

            <div class="flex flex-col gap-5">

                <div class="form-group">
                    <label class="form-label" for="employer">Employer <span style="color: #ef4444">*</span></label>
                    <input
                        type="text"
                        id="employer"
                        name="employer"
                        value="{{ old('employer') }}"
                        class="form-input"
                        placeholder="e.g. Acme Corp"
                        required
                        autofocus
                    >
                    <x-form.error name="employer" />
                </div>

                <div class="form-group">
                    <label class="form-label" for="title">Job title</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        value="{{ old('title') }}"
                        class="form-input"
                        placeholder="e.g. Software Developer"
                    >
                    <x-form.error name="title" />
                </div>

                <div class="flex gap-4">
                    <div class="form-group flex-1">
                        <label class="form-label" for="started_at">Start date <span style="color: #ef4444">*</span></label>
                        <input
                            type="date"
                            id="started_at"
                            name="started_at"
                            value="{{ old('started_at') }}"
                            class="form-input"
                            required
                        >
                        <x-form.error name="started_at" />
                    </div>

                    <div class="form-group flex-1" x-data="{ current: {{ old('ended_at') ? 'false' : 'true' }} }">
                        <label class="form-label" for="ended_at">End date</label>
                        <input
                            type="date"
                            id="ended_at"
                            name="ended_at"
                            value="{{ old('ended_at') }}"
                            class="form-input"
                            x-bind:disabled="current"
                        >
                        <label class="job-form__current-label">
                            <input type="checkbox" x-model="current" class="job-form__current-checkbox">
                            Currently working here
                        </label>
                        <x-form.error name="ended_at" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Work days <span style="color: #ef4444">*</span></label>
                    <div class="job-form__days">
                        @foreach ($allDays as $day)
                            <label class="job-form__day-toggle {{ in_array($day, old('work_days', [])) ? 'job-form__day-toggle--checked' : '' }}">
                                <input
                                    type="checkbox"
                                    name="work_days[]"
                                    value="{{ $day }}"
                                    {{ in_array($day, old('work_days', [])) ? 'checked' : '' }}
                                    x-on:change="$el.closest('label').classList.toggle('job-form__day-toggle--checked', $el.checked)"
                                >
                                {{ substr(ucfirst($day), 0, 2) }}
                            </label>
                        @endforeach
                    </div>
                    <x-form.error name="work_days" />
                </div>

                <div class="flex gap-4">
                    <div class="form-group flex-1">
                        <label class="form-label" for="work_start_time">Start time</label>
                        <input
                            type="time"
                            id="work_start_time"
                            name="work_start_time"
                            value="{{ old('work_start_time') }}"
                            class="form-input"
                        >
                        <x-form.error name="work_start_time" />
                    </div>

                    <div class="form-group flex-1">
                        <label class="form-label" for="work_end_time">End time</label>
                        <input
                            type="time"
                            id="work_end_time"
                            name="work_end_time"
                            value="{{ old('work_end_time') }}"
                            class="form-input"
                        >
                        <x-form.error name="work_end_time" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea
                        id="notes"
                        name="notes"
                        class="form-textarea"
                        rows="3"
                        placeholder="Any additional notes about this job..."
                    >{{ old('notes') }}</textarea>
                    <x-form.error name="notes" />
                </div>

            </div>

            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn btn--primary">Save</button>
                <a href="{{ route('jobs.index') }}" class="btn btn--secondary">Cancel</a>
            </div>

        </form>
    </x-ui.card>

</x-layouts.app>
