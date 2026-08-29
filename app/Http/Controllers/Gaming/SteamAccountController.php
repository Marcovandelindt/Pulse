<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\Steam\CreateSteamAccount;
use App\Actions\Steam\DestroySteamAccount;
use App\Http\Controllers\Controller;
use App\Models\SteamAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SteamAccountController extends Controller
{
    public function index(): View
    {
        $accounts = SteamAccount::orderBy('label')->get();

        return view('pages.steam.settings', compact('accounts'));
    }

    public function store(Request $request, CreateSteamAccount $action): RedirectResponse
    {
        $validated = $request->validate([
            'label'    => ['required', 'string', 'max:64'],
            'steam_id' => ['required', 'string', 'max:64'],
            'api_key'  => ['required', 'string', 'max:255'],
        ]);

        $account = $action->handle($validated);

        return redirect()->route('steam.settings')->with('success', "Account \"{$account->label}\" added.");
    }

    public function activate(SteamAccount $account): RedirectResponse
    {
        $account->activate();

        return redirect()->route('steam.index')->with('success', "Switched to {$account->label}.");
    }

    public function destroy(SteamAccount $account, DestroySteamAccount $action): RedirectResponse
    {
        $action->handle($account);

        return redirect()->route('steam.settings')->with('success', 'Account removed.');
    }
}
