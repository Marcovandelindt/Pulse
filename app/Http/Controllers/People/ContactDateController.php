<?php

declare(strict_types=1);

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StoreContactDateRequest;
use App\Models\Contact;
use App\Models\ContactDate;
use Illuminate\Http\RedirectResponse;

final class ContactDateController extends Controller
{
    public function store(StoreContactDateRequest $request, Contact $contact): RedirectResponse
    {
        $contact->dates()->create($request->validated());

        return redirect()->route('people.show', $contact)->with('success', 'Date added.');
    }

    public function destroy(Contact $contact, ContactDate $date): RedirectResponse
    {
        $date->delete();

        return redirect()->route('people.show', $contact)->with('success', 'Date removed.');
    }
}
