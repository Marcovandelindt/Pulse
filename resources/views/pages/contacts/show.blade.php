<x-layouts.app :title="$contact->name">

    <x-layout.page-header :title="$contact->name">
        <x-slot:actions>
            <a href="{{ route('people.edit', $contact) }}" class="btn btn--secondary btn--sm">Edit</a>
            <form method="POST" action="{{ route('people.destroy', $contact) }}"
                  onsubmit="return confirm('Delete {{ $contact->name }}? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--danger btn--sm">Delete</button>
            </form>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="contact-detail">

        {{-- Avatar --}}
        <div class="contact-detail__header">
            <div class="contact-detail__avatar">
                @if ($contact->photo)
                    <img
                        src="{{ Storage::url($contact->photo) }}"
                        alt="{{ $contact->name }}"
                        class="contact-detail__avatar-img"
                    >
                @else
                    <span class="contact-detail__avatar-initial">
                        {{ strtoupper(substr($contact->name, 0, 1)) }}
                    </span>
                @endif
            </div>

            <div class="contact-detail__header-info">
                <h2 class="contact-detail__name">{{ $contact->name }}</h2>
                @if ($contact->relationshipType)
                    <span class="contact-detail__type">{{ $contact->relationshipType->name }}</span>
                @endif
            </div>
        </div>

        {{-- Meta --}}
        <div class="contact-detail__meta">
            @if ($contact->birthdate)
                <div class="contact-detail__meta-row">
                    <span class="contact-detail__meta-label">Birthday</span>
                    <span class="contact-detail__meta-value">
                        @if ($contact->birth_year_unknown)
                            {{ $contact->birthdate->format('d F') }}
                        @else
                            {{ $contact->birthdate->format('d M Y') }}
                            @if ($contact->age() !== null)
                                &middot; {{ $contact->age() }} {{ $contact->isDeceased() ? 'years old at death' : 'years old' }}
                            @endif
                        @endif
                    </span>
                </div>

                @if ($contact->isDeceased())
                    <div class="contact-detail__meta-row">
                        <span class="contact-detail__meta-label">Passed away</span>
                        <span class="contact-detail__meta-value">{{ $contact->death_date->format('d M Y') }}</span>
                    </div>
                @elseif ($contact->daysUntilBirthday() !== null)
                    <div class="contact-detail__meta-row">
                        <span class="contact-detail__meta-label">Next birthday</span>
                        <span class="contact-detail__meta-value">
                            @if ($contact->daysUntilBirthday() === 0)
                                Today! 🎂
                            @elseif ($contact->daysUntilBirthday() === 1)
                                Tomorrow
                                @if ($contact->nextBirthday())
                                    &middot; {{ $contact->nextBirthday()->format('d M Y') }}
                                @endif
                            @else
                                in {{ $contact->daysUntilBirthday() }} days
                                @if ($contact->nextBirthday())
                                    &middot; {{ $contact->nextBirthday()->format('d M Y') }}
                                @endif
                            @endif
                        </span>
                    </div>
                @endif
            @elseif ($contact->isDeceased())
                <div class="contact-detail__meta-row">
                    <span class="contact-detail__meta-label">Passed away</span>
                    <span class="contact-detail__meta-value">{{ $contact->death_date->format('d M Y') }}</span>
                </div>
            @endif
        </div>

        {{-- Relationships --}}
        @php
            $allRelationships = collect($contact->relationships->map(fn ($r) => ['rel' => $r, 'other' => $r->relatedContact]))
                ->merge($contact->relatedRelationships->map(fn ($r) => ['rel' => $r, 'other' => $r->contact]));
        @endphp
        <div class="contact-detail__dates">
            <div class="contact-detail__dates-label">Relationships</div>

            @if ($allRelationships->isNotEmpty())
                <div class="contact-dates-list">
                    @foreach ($allRelationships as $item)
                        <div class="contact-dates-row">
                            <div class="contact-dates-row__info">
                                <span class="contact-dates-row__label">
                                    {{ $item['rel']->typeLabel() }} —
                                    <a href="{{ route('people.show', $item['other']) }}"
                                       style="color: var(--color-brand-light); text-decoration: none;">
                                        {{ $item['other']->name }}
                                    </a>
                                </span>
                                @if ($item['rel']->date)
                                    <span class="contact-dates-row__date">{{ $item['rel']->date->format('d M Y') }}</span>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('people.relationships.destroy', [$contact, $item['rel']]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--danger btn--sm">Remove</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('people.relationships.store', $contact) }}" class="contact-dates-add">
                @csrf
                <div class="form-group" style="flex: 1; min-width: 9rem;">
                    <label class="form-label">Person</label>
                    <select name="related_contact_id" class="form-select" required>
                        <option value="">— Select —</option>
                        @foreach ($otherContacts as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="flex: 1; min-width: 8rem;">
                    <label class="form-label">Type</label>
                    <input type="text" name="type" list="rel-types" class="form-input"
                           placeholder="e.g. brother" required maxlength="50">
                    <datalist id="rel-types">
                        @foreach (\App\Models\ContactRelationship::suggestions() as $s)
                            <option value="{{ $s }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="form-group" style="flex: 1; min-width: 8rem;">
                    <label class="form-label">Date <span class="form-label__optional">optional</span></label>
                    <input type="date" name="date" class="form-input">
                </div>
                <div class="form-group" style="padding-bottom: 0;">
                    <button type="submit" class="btn btn--primary btn--sm">Link</button>
                </div>
            </form>
        </div>

        {{-- Important dates --}}
        <div class="contact-detail__dates">
            <div class="contact-detail__dates-label">Important dates</div>

            @if ($contact->dates->isNotEmpty())
                <div class="contact-dates-list">
                    @foreach ($contact->dates as $date)
                        <div class="contact-dates-row">
                            <div class="contact-dates-row__info">
                                <span class="contact-dates-row__label">{{ $date->label }}</span>
                                <span class="contact-dates-row__date">{{ $date->date->format('d M Y') }}</span>
                            </div>
                            <form method="POST" action="{{ route('people.dates.destroy', [$contact, $date]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--danger btn--sm">Remove</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('people.dates.store', $contact) }}" class="contact-dates-add">
                @csrf
                <div class="form-group" style="flex: 1; min-width: 8rem;">
                    <label class="form-label">Label</label>
                    <input type="text" name="label" class="form-input" placeholder="e.g. Wedding anniversary" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 8rem;">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-input" required>
                </div>
                <div class="form-group" style="padding-bottom: 0;">
                    <button type="submit" class="btn btn--primary btn--sm">Add</button>
                </div>
            </form>
        </div>

        {{-- Notes --}}
        @if ($contact->notes)
            <div class="contact-detail__notes">
                <div class="contact-detail__notes-label">Notes</div>
                <div class="contact-detail__notes-body">{{ $contact->notes }}</div>
            </div>
        @endif

    </div>

    <x-layout.notification />

</x-layouts.app>
