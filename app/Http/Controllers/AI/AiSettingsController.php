<?php

declare(strict_types=1);

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AiSettingsController extends Controller
{
    public function index(): View
    {
        return view('pages.ai.settings', [
            'personality'   => Setting::getAiPersonality() ?? '',
            'model'         => Setting::getAiModel() ?? '',
            'defaultModel'  => config('ollama.model'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'personality' => ['nullable', 'string', 'max:1000'],
            'model'       => ['nullable', 'string', 'max:100'],
        ]);

        Setting::setAiPersonality($validated['personality'] ?: null);
        Setting::setAiModel($validated['model'] ?: null);

        return back()->with('success', 'Settings saved.');
    }
}
