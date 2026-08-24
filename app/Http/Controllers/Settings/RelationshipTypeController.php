<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreRelationshipTypeRequest;
use App\Models\RelationshipType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class RelationshipTypeController extends Controller
{
    public function index(): View
    {
        $types = RelationshipType::orderBy('sort_order')->orderBy('name')->get();

        return view('pages.settings.relationships', compact('types'));
    }

    public function store(StoreRelationshipTypeRequest $request): RedirectResponse
    {
        $maxOrder = RelationshipType::max('sort_order') ?? 0;

        RelationshipType::create([
            'name' => $request->validated('name'),
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('settings.relationships.index')->with('success', 'Relationship type added.');
    }

    public function destroy(RelationshipType $relationshipType): RedirectResponse
    {
        $relationshipType->delete();

        return redirect()->route('settings.relationships.index')->with('success', 'Relationship type removed.');
    }
}
