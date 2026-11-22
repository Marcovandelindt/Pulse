<x-layouts.app title="Links">

    <x-layout.page-header title="Links">
        <x-slot:actions>
            <a href="{{ route('links.create') }}" class="btn btn--primary btn--sm">+ Add link</a>
        </x-slot:actions>
    </x-layout.page-header>

    @if ($links->isEmpty())
        <x-ui.empty-state title="No links yet" description="Save your first interesting link to get started.">
            <x-slot:action>
                <a href="{{ route('links.create') }}" class="btn btn--primary">+ Add link</a>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <div class="links-grid">
            @foreach ($links as $link)
                <div class="link-card">
                    <div class="link-card__header">
                        <img
                            src="{{ $link->faviconUrl() }}"
                            alt=""
                            class="link-card__favicon"
                            onerror="this.style.display='none'"
                        >
                        <a
                            href="{{ route('links.show', $link) }}"
                            class="link-card__title"
                        >{{ $link->title }}</a>
                    </div>

                    @if ($link->description)
                        <p class="link-card__description">{{ $link->description }}</p>
                    @endif

                    <div class="link-card__footer">
                        <span class="link-card__domain">{{ $link->domain() }}</span>
                        <div class="link-card__actions">
                            <a
                                href="{{ $link->url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn btn--secondary btn--sm"
                            >Visit</a>
                            <a href="{{ route('links.edit', $link) }}" class="btn btn--secondary btn--sm">Edit</a>
                            <form method="POST" action="{{ route('links.destroy', $link) }}" onsubmit="return confirm('Remove this link?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</x-layouts.app>
