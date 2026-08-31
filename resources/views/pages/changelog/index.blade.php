<x-layouts.app title="Changelog">

    <x-layout.page-header title="Changelog" subtitle="Every change made to Pulse, automatically tracked per commit." />

    @if($entries->isEmpty())
        <x-ui.empty-state
            title="No entries yet"
            description="Run php artisan changelog:backfill to import existing commits, or make a new commit."
        />
    @else
        <div class="changelog">
            @foreach($entries as $date => $dayEntries)
                <div class="changelog__day">
                    <div class="changelog__date">
                        {{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}
                    </div>

                    <div class="changelog__entries">
                        @foreach($dayEntries as $entry)
                            <div class="changelog__entry">
                                <div class="changelog__entry-header">
                                    <span class="changelog__type changelog__type--{{ $entry->typeColor() }}">
                                        {{ $entry->type }}
                                    </span>

                                    @if($entry->scope)
                                        <span class="changelog__scope">{{ $entry->scope }}</span>
                                    @endif

                                    <h3 class="changelog__title">{{ $entry->title }}</h3>

                                    @if($entry->commit_hash)
                                        <code class="changelog__hash">{{ substr($entry->commit_hash, 0, 7) }}</code>
                                    @endif

                                    <span class="changelog__time">
                                        {{ $entry->committed_at->format('H:i') }}
                                    </span>
                                </div>

                                @if($entry->description)
                                    <p class="changelog__description">{{ $entry->description }}</p>
                                @endif

                                @if($entry->files_changed && count($entry->files_changed) > 0)
                                    <div class="changelog__files">
                                        @foreach($entry->files_changed as $file)
                                            <span class="changelog__file">{{ $file }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if($entry->stats && $entry->stats['files'] > 0)
                                    <div class="changelog__stats">
                                        <span>{{ $entry->stats['files'] }} {{ Str::plural('file', $entry->stats['files']) }}</span>
                                        @if($entry->stats['insertions'] > 0)
                                            <span class="changelog__stat changelog__stat--add">+{{ $entry->stats['insertions'] }}</span>
                                        @endif
                                        @if($entry->stats['deletions'] > 0)
                                            <span class="changelog__stat changelog__stat--del">−{{ $entry->stats['deletions'] }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</x-layouts.app>
