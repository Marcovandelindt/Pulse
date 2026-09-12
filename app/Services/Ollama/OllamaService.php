<?php

declare(strict_types=1);

namespace App\Services\Ollama;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class OllamaService
{
    public function __construct(
        private readonly DatabaseToolService $tools,
    ) {}

    /** @param array<int, array<string, mixed>> $messages */
    public function chatWithTools(array $messages): string
    {
        $model = Setting::getAiModel() ?? config('ollama.model');

        for ($i = 0; $i < 5; $i++) {
            $response = Http::timeout(120)
                ->post(config('ollama.url').'/api/chat', [
                    'model'    => $model,
                    'messages' => $messages,
                    'tools'    => $this->tools->definitions(),
                    'stream'   => false,
                    'options'  => ['temperature' => 0],
                ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    'Ollama returned HTTP '.$response->status().'. Is Ollama running on '.config('ollama.url').'?'
                );
            }

            $message   = $response->json('message') ?? [];
            $toolCalls = $message['tool_calls'] ?? null;

            if (empty($toolCalls)) {
                return $message['content'] ?? '';
            }

            $messages[] = [
                'role'       => 'assistant',
                'content'    => $message['content'] ?? '',
                'tool_calls' => $toolCalls,
            ];

            foreach ($toolCalls as $call) {
                $name = $call['function']['name'] ?? '';
                $args = $call['function']['arguments'] ?? [];

                if (is_string($args)) {
                    $args = json_decode($args, true) ?? [];
                }

                $messages[] = [
                    'role'    => 'tool',
                    'content' => $this->tools->execute($name, $args),
                ];
            }
        }

        // Fallback after max iterations: one final call without tools
        $response = Http::timeout(120)
            ->post(config('ollama.url').'/api/chat', [
                'model'    => $model,
                'messages' => $messages,
                'stream'   => false,
                'options'  => ['temperature' => 0],
            ]);

        return $response->json('message.content') ?? '';
    }

    /** @param array<int, array{role: string, content: string}> $messages
     *  @return list<string> */
    public function extractMemories(array $messages): array
    {
        $extractPrompt = [
            ['role' => 'system', 'content' => 'You extract personal facts about Marco from conversations. Return ONLY a valid JSON array of short strings (max 12 words each). Each string is one concrete fact about his preferences, habits or personality. Return [] if nothing useful was learned. Never include data facts like steps or game stats — only personal traits.'],
            ['role' => 'user',   'content' => "Extract personal facts from this conversation:\n\n".collect($messages)->map(fn ($m) => strtoupper($m['role']).': '.$m['content'])->join("\n")],
        ];

        try {
            $response = Http::timeout(30)
                ->post(config('ollama.url').'/api/chat', [
                    'model'    => Setting::getAiModel() ?? config('ollama.model'),
                    'messages' => $extractPrompt,
                    'stream'   => false,
                    'options'  => ['temperature' => 0],
                ]);

            $raw = trim($response->json('message.content') ?? '[]');

            // Strip markdown code fences if present
            $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
            $raw = preg_replace('/\s*```$/', '', $raw);

            $decoded = json_decode($raw, true);

            return is_array($decoded) ? array_filter($decoded, fn ($v) => is_string($v) && strlen($v) > 3) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /** @param array<int, array{role: string, content: string}> $messages */
    public function chat(array $messages): string
    {
        $model = Setting::getAiModel() ?? config('ollama.model');

        $response = Http::timeout(120)
            ->post(config('ollama.url').'/api/chat', [
                'model'    => $model,
                'messages' => $messages,
                'stream'   => false,
                'options'  => [
                    'temperature' => 0,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Ollama returned HTTP '.$response->status().'. Is Ollama running on '.config('ollama.url').'?'
            );
        }

        return $response->json('message.content') ?? '';
    }
}
