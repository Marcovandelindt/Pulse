<?php

declare(strict_types=1);

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StoreContactGiftIdeaRequest;
use App\Models\Contact;
use App\Models\ContactGiftIdea;
use Illuminate\Http\RedirectResponse;

final class ContactGiftIdeaController extends Controller
{
    public function store(StoreContactGiftIdeaRequest $request, Contact $contact): RedirectResponse
    {
        $contact->giftIdeas()->create($request->validated());

        return redirect()->route('people.show', $contact)->with('success', 'Gift idea added.');
    }

    public function destroy(Contact $contact, ContactGiftIdea $giftIdea): RedirectResponse
    {
        $giftIdea->delete();

        return redirect()->route('people.show', $contact)->with('success', 'Gift idea removed.');
    }
}
