<x-layouts.app title="Hearing">

    <x-layout.page-header title="Hearing" />

    <x-health.nav />

    <p class="health-page-intro">
        Sound exposure tracked by Apple Watch — both what you listen to through headphones and the ambient noise around you.
        Prolonged exposure to loud sound causes cumulative, irreversible hearing damage, often without any warning signs until it is too late.
        These numbers help you spot risky patterns before they become a problem.
    </p>

    {{-- Stat cards --}}
    <div class="stats-row">
        <x-stats.stat-card
            label="Headphones (latest)"
            :value="$latest?->headphone_audio_exposure_db ? round((float) $latest->headphone_audio_exposure_db, 1) . ' dB' : '—'"
        />
        <x-stats.stat-card
            label="Headphones (avg)"
            :value="$avgHeadphones ? round((float) $avgHeadphones, 1) . ' dB' : '—'"
        />
        <x-stats.stat-card
            label="Environment (latest)"
            :value="$latest?->environmental_audio_exposure_db ? round((float) $latest->environmental_audio_exposure_db, 1) . ' dB' : '—'"
        />
        <x-stats.stat-card
            label="Environment (avg)"
            :value="$avgEnvironment ? round((float) $avgEnvironment, 1) . ' dB' : '—'"
        />
    </div>

    {{-- Headphone exposure chart --}}
    <x-ui.card title="Headphone audio exposure" class="mt-6">
        <p class="health-section-desc">
            The average sound level delivered through your headphones each day, measured in decibels (dBASPL).
            Apple Watch uses the microphone to estimate this.
            The WHO recommends keeping headphone exposure below <strong style="color: var(--color-text-primary);">70 dB</strong> over a full day.
            Above <strong style="color: var(--color-text-primary);">80 dB</strong> for more than 40 hours per week risks permanent damage.
            A comfortable listening level for most people is around 60–70 dB — roughly the volume of a normal conversation.
        </p>
        @if ($headphoneChart['values'])
            <canvas data-chart="line"
                    data-chart-data="{{ json_encode($headphoneChart) }}"
                    style="max-height: 200px;"></canvas>
        @else
            <x-ui.empty-state message="No headphone exposure data yet." />
        @endif
    </x-ui.card>

    {{-- Environmental exposure chart --}}
    <x-ui.card title="Environmental sound levels" class="mt-6">
        <p class="health-section-desc">
            Ambient noise measured by Apple Watch throughout the day — traffic, offices, bars, public transport.
            This reflects your acoustic environment, not something you directly control, but it gives context to your total daily exposure.
            Values above <strong style="color: var(--color-text-primary);">85 dB</strong> are considered hazardous with prolonged exposure (equivalent to a lawnmower or heavy city traffic).
            Consistently elevated environmental exposure is a signal to consider ear protection in those settings.
        </p>
        @if ($environmentChart['values'])
            <canvas data-chart="line"
                    data-chart-data="{{ json_encode($environmentChart) }}"
                    style="max-height: 200px;"></canvas>
        @else
            <x-ui.empty-state message="No environmental audio data yet." />
        @endif
    </x-ui.card>

    {{-- Detail log --}}
    <x-ui.card title="Daily audio exposure log" class="mt-6">
        <p class="health-section-desc">
            Day-by-day breakdown of your sound exposure.
            Headphone values are highlighted when they approach or exceed the safe threshold of 70 dB.
        </p>
        @if ($history->isEmpty())
            <x-ui.empty-state message="No hearing data yet." />
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Headphones</th>
                        <th>Environment</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($history->reverse() as $entry)
                        <tr>
                            <td>{{ $entry->date->format('d M Y') }}</td>
                            <td>
                                @if ($entry->headphone_audio_exposure_db !== null)
                                    <span class="{{ (float) $entry->headphone_audio_exposure_db >= 80 ? 'text-red-400' : ((float) $entry->headphone_audio_exposure_db >= 75 ? 'text-yellow-400' : '') }}">
                                        {{ round((float) $entry->headphone_audio_exposure_db, 1) }} dB
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $entry->environmental_audio_exposure_db !== null ? round((float) $entry->environmental_audio_exposure_db, 1) . ' dB' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-ui.card>

    <x-layout.notification />

</x-layouts.app>
