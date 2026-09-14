<x-layouts.app title="Review extracted sessions">
    <div class="page-header">
        <div class="page-header__left">
            <a href="{{ route('nintendo.import') }}" class="page-header__back">Import</a>
            <h1 class="page-header__title">Review sessions</h1>
        </div>
    </div>

    @if(empty($sessions))
        <x-ui.card class="mb-6">
            <div style="text-align:center;padding:2rem;color:var(--color-text-muted);">
                <p style="font-size:.9375rem;margin-bottom:.5rem;">No sessions could be extracted from the screenshot.</p>
                <p style="font-size:.8125rem;">Try a cleaner screenshot, or make sure the Play Activity list is fully visible.</p>
            </div>
        </x-ui.card>

        {{-- Show raw OCR text for debugging --}}
        @if($rawText)
            <x-ui.card title="Raw OCR output">
                <pre style="font-size:.75rem;color:var(--color-text-muted);white-space:pre-wrap;word-break:break-word;max-height:20rem;overflow-y:auto;">{{ $rawText }}</pre>
            </x-ui.card>
        @endif

        <div style="margin-top:1.25rem;">
            <a href="{{ route('nintendo.import') }}" class="btn btn--secondary">Try again</a>
        </div>
    @else
        <form method="POST" action="{{ route('nintendo.import.confirm') }}">
            @csrf

            <x-ui.card title="{{ count($sessions) }} sessions extracted" class="mb-4">
                <p style="font-size:.8125rem;color:var(--color-text-muted);margin-bottom:1.25rem;">
                    Review and correct any mistakes. Uncheck rows you don't want to import.
                </p>

                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                        <thead>
                            <tr style="border-bottom:1px solid var(--color-border);">
                                <th style="padding:.5rem .75rem;text-align:left;font-size:.6875rem;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;width:2rem;"></th>
                                <th style="padding:.5rem .75rem;text-align:left;font-size:.6875rem;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;">Game</th>
                                <th style="padding:.5rem .75rem;text-align:left;font-size:.6875rem;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;">Date</th>
                                <th style="padding:.5rem .75rem;text-align:left;font-size:.6875rem;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;">Minutes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sessions as $i => $session)
                                @php $needsReview = $session['needs_review'] ?? false; @endphp
                                <tr
                                    style="border-bottom:1px solid var(--color-border);{{ $needsReview ? 'background:rgba(251,191,36,.05);' : '' }}"
                                    x-data="{ checked: true }"
                                >
                                    <td style="padding:.625rem .75rem;">
                                        <input
                                            type="checkbox"
                                            name="sessions[{{ $i }}][import]"
                                            value="1"
                                            x-model="checked"
                                            style="accent-color:var(--color-brand);"
                                        >
                                    </td>
                                    <td style="padding:.625rem .75rem;" :style="!checked && 'opacity:.4'">
                                        <input
                                            type="text"
                                            name="sessions[{{ $i }}][game]"
                                            value="{{ $session['game'] }}"
                                            class="form-input form-input--sm"
                                            style="min-width:200px;"
                                            :disabled="!checked"
                                        >
                                    </td>
                                    <td style="padding:.625rem .75rem;" :style="!checked && 'opacity:.4'">
                                        <input
                                            type="date"
                                            name="sessions[{{ $i }}][date]"
                                            value="{{ $session['date'] ?? '' }}"
                                            class="form-input form-input--sm"
                                            :disabled="!checked"
                                        >
                                    </td>
                                    <td style="padding:.625rem .75rem;" :style="!checked && 'opacity:.4'">
                                        <div style="display:flex;align-items:center;gap:.5rem;">
                                            <input
                                                type="number"
                                                name="sessions[{{ $i }}][minutes]"
                                                value="{{ $session['minutes'] ?? '' }}"
                                                class="form-input form-input--sm"
                                                style="width:5rem;{{ $needsReview ? 'border-color:#f59e0b;' : '' }}"
                                                min="1"
                                                max="1440"
                                                :disabled="!checked"
                                            >
                                            @if($needsReview)
                                                <span title="Nintendo toonde 'korte tijd' — vul het exacte aantal minuten in" style="color:#f59e0b;font-size:.75rem;white-space:nowrap;cursor:help;">
                                                    korte tijd
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            {{-- Raw OCR collapsible --}}
            <div x-data="{ open: false }" style="margin-bottom:1.25rem;">
                <button
                    type="button"
                    @click="open = !open"
                    style="font-size:.8125rem;color:var(--color-text-muted);background:none;border:none;cursor:pointer;padding:0;display:flex;align-items:center;gap:.375rem;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:.875rem;height:.875rem;transition:transform var(--transition-base);" :style="open && 'transform:rotate(90deg)'">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                    Show raw OCR output
                </button>
                <div x-show="open" x-transition style="margin-top:.75rem;">
                    <pre style="font-size:.75rem;color:var(--color-text-muted);white-space:pre-wrap;word-break:break-word;background:var(--color-bg-tertiary);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:1rem;max-height:16rem;overflow-y:auto;">{{ $rawText }}</pre>
                </div>
            </div>

            <div style="display:flex;gap:.75rem;">
                <a href="{{ route('nintendo.import') }}" class="btn btn--secondary">Upload another</a>
                <button type="submit" class="btn btn--primary">Import selected</button>
            </div>
        </form>
    @endif
</x-layouts.app>
