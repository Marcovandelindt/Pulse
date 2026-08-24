<x-layouts.app title="Contacts">

    <x-layout.page-header title="Contacts">
        <x-slot:actions>
            <a href="{{ route('settings.relationships.index') }}" class="btn btn--secondary btn--sm">Relationships</a>
            <a href="{{ route('people.create') }}" class="btn btn--primary btn--sm">+ Add person</a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Upcoming birthdays --}}
    @if ($upcoming->isNotEmpty())
        <div class="people-upcoming">
            <h2 class="people-section-title">Upcoming birthdays</h2>
            <div class="flex flex-wrap gap-3">
                @foreach ($upcoming as $contact)
                    <a href="{{ route('people.show', $contact) }}" class="people-birthday-pill">
                        @if ($contact->photo)
                            <img
                                src="{{ Storage::url($contact->photo) }}"
                                alt="{{ $contact->name }}"
                                class="people-birthday-pill__avatar people-birthday-pill__avatar--photo"
                            >
                        @else
                            <span class="people-birthday-pill__avatar">
                                {{ strtoupper(substr($contact->name, 0, 1)) }}
                            </span>
                        @endif
                        <span class="people-birthday-pill__name">{{ $contact->name }}</span>
                        <span class="people-birthday-pill__date">{{ $contact->birthdate->format('d M') }}</span>
                        @if ($contact->daysUntilBirthday() === 0)
                            <span class="people-birthday-pill__countdown people-birthday-pill__countdown--today">Today! 🎂</span>
                        @else
                            <span class="people-birthday-pill__countdown">in {{ $contact->daysUntilBirthday() }} days</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Grouped contacts --}}
    @if ($contacts->isEmpty())
        <x-ui.empty-state title="No contacts yet" description="Add your first person to get started.">
            <x-slot:action>
                <a href="{{ route('people.create') }}" class="btn btn--primary">+ Add person</a>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        @foreach ($contacts as $groupName => $group)
            <div class="people-group">
                <h2 class="people-section-title">{{ $groupName }}</h2>
                <div class="people-grid">
                    @foreach ($group as $contact)
                        <a href="{{ route('people.show', $contact) }}" class="people-card">
                            <div class="people-card__avatar">
                                @if ($contact->photo)
                                    <img
                                        src="{{ Storage::url($contact->photo) }}"
                                        alt="{{ $contact->name }}"
                                        class="people-card__avatar-img"
                                    >
                                @else
                                    <span class="people-card__avatar-initial">
                                        {{ strtoupper(substr($contact->name, 0, 1)) }}
                                    </span>
                                @endif
                            </div>
                            <div class="people-card__info">
                                <div class="people-card__name">{{ $contact->name }}</div>
                                @if ($contact->birthdate)
                                    <div class="people-card__meta">
                                        {{ $contact->birth_year_unknown ? $contact->birthdate->format('d M') : $contact->birthdate->format('d M Y') }}
                                        @if ($contact->age() !== null)
                                            &middot; {{ $contact->age() }} yr
                                        @endif
                                    </div>
                                @endif
                                @php
                                    $cardRels = collect($contact->relationships->map(fn ($r) => ['type' => $r->type, 'name' => $r->relatedContact->name]))
                                        ->merge($contact->relatedRelationships->map(fn ($r) => ['type' => $r->type, 'name' => $r->contact->name]));
                                @endphp
                                @if ($contact->death_date)
                                    <div class="people-card__meta people-card__meta--deceased">
                                        Sterfdag · {{ $contact->death_date->format('d M Y') }}
                                    </div>
                                @endif
                                @foreach ($cardRels as $rel)
                                    <div class="people-card__meta people-card__meta--rel">
                                        {{ ucfirst($rel['type']) }} · {{ $rel['name'] }}
                                    </div>
                                @endforeach
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif

    <x-layout.notification />

</x-layouts.app>
