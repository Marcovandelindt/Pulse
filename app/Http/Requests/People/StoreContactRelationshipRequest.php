<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use App\Models\Contact;
use App\Models\ContactRelationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreContactRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Contact $contact */
        $contact = $this->route('contact');

        return [
            'related_contact_id' => ['required', 'exists:contacts,id', Rule::notIn([$contact->id])],
            'type' => ['required', 'string', Rule::in(ContactRelationship::types())],
            'date' => ['nullable', 'date'],
        ];
    }
}
