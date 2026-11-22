<x-layouts.app title="{{ $link->title }}">

    <x-layout.page-header :title="$link->title">
        <x-slot:actions>
            <a href="{{ route('links.index') }}" class="btn btn--secondary btn--sm">← Back</a>
            <a href="{{ route('links.edit', $link) }}" class="btn btn--secondary btn--sm">Edit</a>
            <form method="POST" action="{{ route('links.destroy', $link) }}" onsubmit="return confirm('Remove this link?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--danger btn--sm">Delete</button>
            </form>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card>
        <div class="flex flex-col gap-4">

            <div class="flex items-center gap-3">
                <img
                    src="{{ $link->faviconUrl() }}"
                    alt=""
                    style="width:1.25rem;height:1.25rem;border-radius:var(--radius-sm);"
                    onerror="this.style.display='none'"
                >
                <a
                    href="{{ $link->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="color:var(--color-brand);font-size:0.875rem;word-break:break-all;"
                >{{ $link->url }}</a>
            </div>

            @if ($link->description)
                <p style="color:var(--color-text-muted);font-size:0.9375rem;line-height:1.65;">{{ $link->description }}</p>
            @endif

            <div style="font-size:0.75rem;color:var(--color-text-muted);margin-top:0.5rem;">
                Saved {{ $link->created_at->diffForHumans() }}
            </div>

        </div>
    </x-ui.card>

</x-layouts.app>
