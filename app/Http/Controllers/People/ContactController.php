<?php

declare(strict_types=1);

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StoreContactRequest;
use App\Http\Requests\People\UpdateContactRequest;
use App\Models\Contact;
use App\Models\RelationshipType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class ContactController extends Controller
{
    public function index(): View
    {
        $contacts = Contact::with('relationshipType')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Contact $c) => $c->relationshipType?->name ?? 'Other');

        $upcoming = Contact::whereNotNull('birthdate')
            ->whereNull('death_date')
            ->get()
            ->filter(fn (Contact $c) => $c->daysUntilBirthday() !== null && $c->daysUntilBirthday() <= 30)
            ->sortBy(fn (Contact $c) => $c->daysUntilBirthday());

        return view('pages.contacts.index', compact('contacts', 'upcoming'));
    }

    public function create(): View
    {
        $relationshipTypes = RelationshipType::orderBy('sort_order')->orderBy('name')->get();

        return view('pages.contacts.create', compact('relationshipTypes'));
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('contacts', 'public');
        }

        Contact::create($data);

        return redirect()->route('people.index')->with('success', "{$data['name']} added.");
    }

    public function show(Contact $contact): View
    {
        $contact->load('relationshipType', 'calendarEvents', 'dates');

        return view('pages.contacts.show', compact('contact'));
    }

    public function edit(Contact $contact): View
    {
        $relationshipTypes = RelationshipType::orderBy('sort_order')->orderBy('name')->get();

        return view('pages.contacts.edit', compact('contact', 'relationshipTypes'));
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('contacts', 'public');
        }

        $contact->update($data);

        return redirect()->route('people.show', $contact)->with('success', 'Contact updated.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()->route('people.index')->with('success', "{$contact->name} removed.");
    }
}
