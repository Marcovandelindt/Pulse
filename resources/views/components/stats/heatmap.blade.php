@props([
    'label'   => '',
    'entries' => [],   // ['Y-m-d' => int]
    'unit'    => '',
    'scheme'  => 'green',  // green | purple | pink | amber
    'format'  => null,     // callable|null for custom value formatting
])

@php
    use Illuminate\Support\Carbon;

    $today     = now()->startOfDay();
    $rangeStart = $today->copy()->subDays(364);
    $gridStart  = $rangeStart->copy()->startOfWeek(Carbon::MONDAY);

    $maxValue = !empty($entries) ? max(array_values($entries)) : 0;

    $level = function (int $value) use ($maxValue): int {
        if ($value === 0 || $maxValue === 0) return 0;
        $ratio = $value / $maxValue;
        return match (true) {
            $ratio <= 0.25 => 1,
            $ratio <= 0.50 => 2,
            $ratio <= 0.75 => 3,
            default        => 4,
        };
    };

    $formatValue = function (int $value) use ($unit, $format): string {
        if ($value === 0) return 'No data';
        if ($format !== null) return ($format)($value);
        return number_format($value) . ($unit ? ' ' . $unit : '');
    };

    $weeks       = [];
    $monthLabels = [];
    $current     = $gridStart->copy();
    $weekIndex   = 0;
    $lastMonth   = null;

    while ($current->lte($today)) {
        $week = [];
        for ($d = 0; $d < 7; $d++) {
            $day     = $current->copy()->addDays($d);
            $dateStr = $day->format('Y-m-d');
            $inRange = $day->gte($rangeStart) && $day->lte($today);
            $value   = $inRange ? ($entries[$dateStr] ?? 0) : 0;

            $week[] = [
                'date'    => $day,
                'value'   => $value,
                'level'   => $inRange ? $level($value) : -1,
                'inRange' => $inRange,
                'label'   => $day->format('M j, Y') . ': ' . $formatValue($value),
            ];
        }
        $weeks[] = $week;

        // Month label on the Monday of the week where the month changes
        $monday = $current;
        if ($monday->gte($rangeStart) && $monday->format('Y-m') !== $lastMonth) {
            $monthLabels[$weekIndex] = $monday->format('M');
            $lastMonth = $monday->format('Y-m');
        }

        $current->addWeek();
        $weekIndex++;
    }

    $totalEntries  = count(array_filter($entries));
    $totalValue    = array_sum($entries);
    $summary       = $totalEntries > 0
        ? number_format($totalValue) . ' ' . $unit . ' across ' . $totalEntries . ' days'
        : 'No data in the past year';
@endphp

<div class="heatmap heatmap--{{ $scheme }}" x-data="{ tip: '' }">
    <div class="heatmap__header">
        <span class="heatmap__label">{{ $label }}</span>
        <span class="heatmap__summary" x-text="tip || '{{ $summary }}'"></span>
    </div>

    <div class="heatmap__grid">
        {{-- Month labels row --}}
        <div class="heatmap__months">
            @foreach ($weeks as $wi => $week)
                <div class="heatmap__month-cell">
                    @if (isset($monthLabels[$wi]))
                        <span class="heatmap__month-label">{{ $monthLabels[$wi] }}</span>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Cells --}}
        <div class="heatmap__weeks">
            @foreach ($weeks as $week)
                <div class="heatmap__week">
                    @foreach ($week as $cell)
                        <div
                            class="heatmap__cell {{ $cell['level'] === -1 ? 'heatmap__cell--empty' : 'heatmap__cell--level-' . $cell['level'] }}"
                            @if ($cell['inRange'])
                                @mouseenter="tip = '{{ $cell['label'] }}'"
                                @mouseleave="tip = ''"
                            @endif
                        ></div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <div class="heatmap__legend">
        <span class="heatmap__legend-label">Less</span>
        <div class="heatmap__cell heatmap__cell--level-0"></div>
        <div class="heatmap__cell heatmap__cell--level-1"></div>
        <div class="heatmap__cell heatmap__cell--level-2"></div>
        <div class="heatmap__cell heatmap__cell--level-3"></div>
        <div class="heatmap__cell heatmap__cell--level-4"></div>
        <span class="heatmap__legend-label">More</span>
    </div>
</div>
