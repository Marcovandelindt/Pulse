<?php

declare(strict_types=1);

namespace App\Services\Ollama;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class OllamaService
{
    /** @param array<int, array{role: string, content: string}> $messages */
    public function chat(array $messages): string
    {
        $model = Setting::getAiModel() ?? config('ollama.model');

        $response = Http::timeout(120)
            ->post(config('ollama.url').'/api/chat', [
                'model'    => $model,
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
