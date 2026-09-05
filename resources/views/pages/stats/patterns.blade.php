<x-layouts.app title="Cross-domain patterns">

    <x-layout.page-header title="Cross-domain patterns" subtitle="How your habits connect across health, music, gaming and media." />

    <div class="patterns">

        {{-- ── Day-of-week profile ─────────────────────────────── --}}
        <div class="card">
            <div class="card__header">
                <span class="card__header-title">Activity by day of week</span>
            </div>
            <div class="card__body">
                <div class="patterns__dow-legend">
                    <span class="patterns__dow-legend-dot patterns__dow-legend-dot--steps"></span> Avg steps
                    <span class="patterns__dow-legend-dot patterns__dow-legend-dot--plays"></span> Avg music plays
                    <span class="patterns__dow-legend-dot patterns__dow-legend-dot--gaming"></span> Avg gaming min
                </div>

                @php
                    $dow     = $stats['day_of_week'];
                    $maxStep = collect($dow)->max('avg_steps') ?: 1;
                    $maxPlay = collect($dow)->max('avg_plays') ?: 1;
                    $maxGame = collect($dow)->max('avg_gaming_min') ?: 1;
                @endphp

                <div class="patterns__dow-chart">
                    @foreach($dow as $day)
                        <div class="patterns__dow-day">
                            <div class="patterns__dow-bars">
                                <div class="patterns__dow-bar patterns__dow-bar--steps"
                                     style="height: {{ round($day['avg_steps'] / $maxStep * 100) }}%"
                                     title="{{ number_format($day['avg_steps']) }} steps"></div>
                                <div class="patterns__dow-bar patterns__dow-bar--plays"
                                     style="height: {{ round($day['avg_plays'] / $maxPlay * 100) }}%"
                                     title="{{ $day['avg_plays'] }} plays"></div>
                                <div class="patterns__dow-bar patterns__dow-bar--gaming"
                                     style="height: {{ round($day['avg_gaming_min'] / $maxGame * 100) }}%"
                                     title="{{ $day['avg_gaming_min'] }} min"></div>
                            </div>
                            <span class="patterns__dow-label">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="patterns__dow-values">
                    @foreach($dow as $day)
                        <div class="patterns__dow-value-group">
                            <span class="patterns__dow-val patterns__dow-val--steps">{{ number_format($day['avg_steps']) }}</span>
                            <span class="patterns__dow-val patterns__dow-val--plays">{{ $day['avg_plays'] }}</span>
                            <span class="patterns__dow-val patterns__dow-val--gaming">{{ $day['avg_gaming_min'] }}m</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Comparison cards ────────────────────────────────── --}}
        <div class="patterns__comparisons">

            {{-- Gaming vs health --}}
            @if($stats['gaming_vs_health'])
                @php $g = $stats['gaming_vs_health']; @endphp
                <div class="card patterns__compare-card">
                    <div class="card__body">
                        <p class="patterns__compare-title">Steps on gaming days vs. rest days</p>
                        <div class="patterns__compare-row">
                            <div class="patterns__compare-half">
                                <span class="patterns__compare-label patterns__compare-label--gaming">Gaming days</span>
                                <span class="patterns__compare-value">{{ number_format($g['avg_steps_gaming']) }}</span>
                                <span class="patterns__compare-sub">avg steps</span>
                            </div>
                            <div class="patterns__compare-divider">
                                @if($g['diff_pct'] !== null)
                                    <span class="patterns__compare-diff {{ $g['diff_pct'] >= 0 ? 'patterns__compare-diff--up' : 'patterns__compare-diff--down' }}">
                                        {{ $g['diff_pct'] > 0 ? '+' : '' }}{{ $g['diff_pct'] }}%
                                    </span>
                                @endif
                            </div>
                            <div class="patterns__compare-half">
                                <span class="patterns__compare-label">Rest days</span>
                                <span class="patterns__compare-value">{{ number_format($g['avg_steps_rest']) }}</span>
                                <span class="patterns__compare-sub">avg steps</span>
                            </div>
                        </div>
                        <p class="patterns__compare-footnote">Based on {{ number_format($g['gaming_days_count']) }} gaming days</p>
                    </div>
                </div>
            @endif

            {{-- Music vs health --}}
            @if($stats['music_vs_health'])
                @php $m = $stats['music_vs_health']; @endphp
                <div class="card patterns__compare-card">
                    <div class="card__body">
                        <p class="patterns__compare-title">Music plays: active days vs. low-step days</p>
                        <div class="patterns__compare-row">
                            <div class="patterns__compare-half">
                                <span class="patterns__compare-label patterns__compare-label--music">Active days</span>
                                <span class="patterns__compare-value">{{ $m['avg_plays_active'] }}</span>
                                <span class="patterns__compare-sub">avg plays</span>
                            </div>
                            <div class="patterns__compare-divider">
                                @if($m['diff_pct'] !== null)
                                    <span class="patterns__compare-diff {{ $m['diff_pct'] >= 0 ? 'patterns__compare-diff--up' : 'patterns__compare-diff--down' }}">
                                        {{ $m['diff_pct'] > 0 ? '+' : '' }}{{ $m['diff_pct'] }}%
                                    </span>
                                @endif
                            </div>
                            <div class="patterns__compare-half">
                                <span class="patterns__compare-label">Low-step days</span>
                                <span class="patterns__compare-value">{{ $m['avg_plays_rest'] }}</span>
                                <span class="patterns__compare-sub">avg plays</span>
                            </div>
                        </div>
                        <p class="patterns__compare-footnote">Active = ≥ {{ number_format($m['overall_avg_steps']) }} steps (your overall avg)</p>
                    </div>
                </div>
            @endif

            {{-- Media vs gaming --}}
            @if($stats['media_vs_gaming'])
                @php $mv = $stats['media_vs_gaming']; @endphp
                <div class="card patterns__compare-card">
                    <div class="card__body">
                        <p class="patterns__compare-title">Episodes & films watched: gaming days vs. rest</p>
                        <div class="patterns__compare-row">
                            <div class="patterns__compare-half">
                                <span class="patterns__compare-label patterns__compare-label--gaming">Gaming days</span>
                                <span class="patterns__compare-value">{{ $mv['avg_media_gaming'] }}</span>
                                <span class="patterns__compare-sub">avg items</span>
                            </div>
                            <div class="patterns__compare-divider">
                                @php
                                    $mediaDiff = $mv['avg_media_rest'] > 0
                                        ? round(($mv['avg_media_gaming'] - $mv['avg_media_rest']) / $mv['avg_media_rest'] * 100, 1)
                                        : null;
                                @endphp
                                @if($mediaDiff !== null)
                                    <span class="patterns__compare-diff {{ $mediaDiff >= 0 ? 'patterns__compare-diff--up' : 'patterns__compare-diff--down' }}">
                                        {{ $mediaDiff > 0 ? '+' : '' }}{{ $mediaDiff }}%
                                    </span>
                                @endif
                            </div>
                            <div class="patterns__compare-half">
                                <span class="patterns__compare-label">Rest days</span>
                                <span class="patterns__compare-value">{{ $mv['avg_media_rest'] }}</span>
                                <span class="patterns__compare-sub">avg items</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>

    </div>

</x-layouts.app>
