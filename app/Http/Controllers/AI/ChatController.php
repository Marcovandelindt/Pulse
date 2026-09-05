<?php

declare(strict_types=1);

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiMemory;
use App\Services\Ollama\ContextBuilderService;
use App\Services\Ollama\OllamaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class ChatController extends Controller
{
    public function __construct(
        private readonly OllamaService $ollama,
        private readonly ContextBuilderService $context,
    ) {}

    public function index(): RedirectResponse
    {
        $latest = AiConversation::latest()->first();

        if ($latest) {
            return redirect()->route('ai.chat.show', $latest);
        }

        $conversation = AiConversation::create();

        return redirect()->route('ai.chat.show', $conversation);
    }

    public function show(AiConversation $conversation): View
    {
        $conversations = AiConversation::latest()->get();
        $messages      = $conversation->messages;

        return view('pages.ai.chat', compact('conversation', 'conversations', 'messages'));
    }

    public function store(): RedirectResponse
    {
        $conversation = AiConversation::create();

        return redirect()->route('ai.chat.show', $conversation);
    }

    public function send(Request $request, AiConversation $conversation): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        // Save user message
        $conversation->messages()->create([
            'role'    => 'user',
            'content' => $validated['message'],
        ]);

        // Auto-title from first message
        if ($conversation->title === null) {
            $conversation->update([
                'title' => mb_substr($validated['message'], 0, 50),
            ]);
        }

        // Build full message array for Ollama
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get(['role', 'content'])
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->all();

        $prompt = array_merge(
            [['role' => 'system', 'content' => $this->context->buildSystemPrompt()]],
            $messages,
        );

        try {
            $reply = $this->ollama->chat($prompt);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }

        // Save assistant reply
        $conversation->messages()->create([
            'role'    => 'assistant',
            'content' => $reply,
        ]);

        // Extract and save memories from this exchange
        $this->extractAndSaveMemories($messages, $reply);

        return response()->json([
            'reply' => $reply,
            'title' => $conversation->fresh()->title,
        ]);
    }

    public function destroy(AiConversation $conversation): RedirectResponse
    {
        $conversation->delete();

        $next = AiConversation::latest()->first();

        if ($next) {
            return redirect()->route('ai.chat.show', $next);
        }

        $new = AiConversation::create();

        return redirect()->route('ai.chat.show', $new);
    }

    /** @param array<int, array{role: string, content: string}> $messages */
    private function extractAndSaveMemories(array $messages, string $lastReply): void
    {
        // Only extract after at least 2 exchanges
        if (count($messages) < 3) {
            return;
        }

        $facts = $this->ollama->extractMemories(
            array_merge($messages, [['role' => 'assistant', 'content' => $lastReply]])
        );

        foreach ($facts as $fact) {
            $fact = trim($fact);
            if (!$fact) {
                continue;
            }

            // Skip if a very similar memory already exists
            $exists = AiMemory::whereRaw('LOWER(content) = ?', [strtolower($fact)])->exists();
            if (!$exists) {
                AiMemory::create(['content' => $fact]);
            }
        }
    }
}
