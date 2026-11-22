<?php

declare(strict_types=1);

namespace App\Http\Controllers\Links;

use App\Http\Controllers\Controller;
use App\Http\Requests\Links\StoreLinkRequest;
use App\Http\Requests\Links\UpdateLinkRequest;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class LinkController extends Controller
{
    public function index(): View
    {
        $links = Link::latest()->get();

        return view('pages.links.index', compact('links'));
    }

    public function create(): View
    {
        return view('pages.links.create');
    }

    public function store(StoreLinkRequest $request): RedirectResponse
    {
        Link::create($request->validated());

        return redirect()->route('links.index')->with('success', 'Link saved.');
    }

    public function show(Link $link): View
    {
        return view('pages.links.show', compact('link'));
    }

    public function edit(Link $link): View
    {
        return view('pages.links.edit', compact('link'));
    }

    public function update(UpdateLinkRequest $request, Link $link): RedirectResponse
    {
        $link->update($request->validated());

        return redirect()->route('links.show', $link)->with('success', 'Link updated.');
    }

    public function destroy(Link $link): RedirectResponse
    {
        $link->delete();

        return redirect()->route('links.index')->with('success', 'Link removed.');
    }
}
