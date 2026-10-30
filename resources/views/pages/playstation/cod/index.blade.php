<x-layouts.app title="Call of Duty — PlayStation">

    <x-layout.page-header title="Call of Duty">
        <x-slot:actions>
            <a href="{{ route('playstation.cod.stats') }}" class="btn btn--secondary btn--sm">Stats</a>
            @if($pendingCount > 0)
                <a href="{{ route('playstation.cod.review') }}" class="btn btn--secondary btn--sm">
                    Review
                    <span class="cod-pending-badge">{{ $pendingCount }}</span>
                </a>
            @endif
            <a href="{{ route('playstation.index') }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Upload queued flash --}}
    @if(session('upload_queued'))
        @php $q = session('upload_queued'); @endphp
        <div class="cod-upload-summary mb-6">
            @if($q['queued'] > 0)
                <span class="cod-upload-summary__item cod-upload-summary__item--pending">
                    {{ $q['queued'] }} {{ Str::plural('screenshot', $q['queued']) }} queued for processing — check back in a minute
                </span>
            @endif
            @if($q['skipped'] > 0)
                <span class="cod-upload-summary__item cod-upload-summary__item--duplicate">
                    {{ $q['skipped'] }} already processed
                </span>
            @endif
        </div>
    @endif

    {{-- Upload form --}}
    <x-ui.card title="Upload Screenshots" class="mb-6">
        <form action="{{ route('playstation.cod.screenshots.store') }}"
              method="POST"
              enctype="multipart/form-data"
              x-data="{ files: [] }">
            @csrf

            <div class="cod-upload">
                <label class="cod-upload__label">
                    <input type="file"
                           name="screenshots[]"
                           multiple
                           accept="image/jpeg,image/png"
                           class="cod-upload__input"
                           @change="files = Array.from($event.target.files).map(f => f.name)">
                    <div class="cod-upload__zone">
                        <p class="cod-upload__hint" x-show="files.length === 0">
                            Click to select screenshots, or drag &amp; drop — up to 10 at once
                        </p>
                        <ul class="cod-upload__filelist" x-show="files.length > 0">
                            <template x-for="name in files" :key="name">
                                <li x-text="name"></li>
                            </template>
                        </ul>
                    </div>
                </label>
            </div>

            @error('screenshots')
                <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
            @enderror
            @error('screenshots.*')
                <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
            @enderror

            <div class="mt-4 flex justify-end">
                <button type="submit" class="btn btn--primary">Process Screenshots</button>
            </div>
        </form>
    </x-ui.card>

    {{-- Match list --}}
    @if($matches->isEmpty())
        <x-ui.empty-state
            title="No matches yet"
            description="Upload screenshots from the PlayStation 5 Combat Record screen to get started."
        />
    @else
        <x-ui.card title="Recent Matches">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Map</th>
                            <th>Mode</th>
                            <th>Result</th>
                            <th>Score</th>
                            <th>K / D</th>
                            <th>K/D Ratio</th>
                            <th>Damage</th>
                            <th>Weapon</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($matches as $match)
                            <tr>
                                <td class="text-sm" style="color: var(--color-text-muted)">
                                    {{ $match->estimated_date->format('d M Y') }}
                                </td>
                                <td>{{ $match->map->name }}</td>
                                <td>{{ $match->mode->name }}</td>
                                <td>
                                    <span class="badge {{ $match->result === \App\Enums\CodResult::Victory ? 'badge--green' : 'badge--red' }}">
                                        {{ $match->result->label() }}
                                    </span>
                                </td>
                                <td class="text-sm" style="color: var(--color-text-muted)">
                                    {{ $match->team_score }} – {{ $match->opponent_score }}
                                </td>
                                <td>{{ $match->kills }} / {{ $match->deaths }}</td>
                                <td>{{ $match->kd_ratio }}</td>
                                <td>{{ number_format($match->damage) }}</td>
                                <td class="text-sm" style="color: var(--color-text-muted)">
                                    {{ $match->topWeapon?->name ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($matches->hasPages())
                <div class="mt-4">
                    {{ $matches->links() }}
                </div>
            @endif
        </x-ui.card>
    @endif

</x-layouts.app>
