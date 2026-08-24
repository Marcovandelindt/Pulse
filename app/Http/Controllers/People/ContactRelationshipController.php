<?php

declare(strict_types=1);

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StoreContactRelationshipRequest;
use App\Models\Contact;
use App\Models\ContactRelationship;
use Illuminate\Http\RedirectResponse;

final class ContactRelationshipController extends Controller
{
    public function store(StoreContactRelationshipRequest $request, Contact $contact): RedirectResponse
    {
        $contact->relationships()->create($request->validated());

        return redirect()->route('people.show', $contact)->with('success', 'Relationship added.');
    }

    public function destroy(Contact $contact, ContactRelationship $relationship): RedirectResponse
    {
        $relationship->delete();

        return redirect()->route('people.show', $contact)->with('success', 'Relationship removed.');
    }
}
