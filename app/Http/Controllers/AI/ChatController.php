<?php

declare(strict_types=1);

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Services\Ollama\ContextBuilderService;
use App\Services\Ollama\OllamaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class ChatController extends Controller
{
    public function __construct(
        private readonly OllamaService $ollama,
        private readonly ContextBuilderService $context,
    ) {}

    public function index(): View
    {
        return view('pages.ai.chat');
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'messages'           => ['required', 'array', 'min:1', 'max:50'],
            'messages.*.role'    => ['required', 'string', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:4000'],
        ]);

        $messages = array_merge(
            [['role' => 'system', 'content' => $this->context->buildSystemPrompt()]],
            $validated['messages'],
        );

        try {
            $reply = $this->ollama->chat($messages);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }

        return response()->json(['reply' => $reply]);
    }
}
