<?php

declare(strict_types=1);

namespace App\Services\Ollama;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class OllamaService
{
    /** @param array<int, array{role: string, content: string}> $messages */
    public function chat(array $messages): string
    {
        $response = Http::timeout(120)
            ->post(config('ollama.url').'/api/chat', [
                'model'    => config('ollama.model'),
                'messages' => $messages,
                'stream'   => false,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Ollama returned HTTP '.$response->status().'. Is Ollama running on '.config('ollama.url').'?'
            );
        }

        return $response->json('message.content') ?? '';
    }
}
