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
                    'model' => $model,
                    'messages' => $messages,
                    'tools' => $this->tools->definitions(),
                    'stream' => false,
                    'options' => ['temperature' => 0],
                ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    'Ollama returned HTTP '.$response->status().'. Is Ollama running on '.config('ollama.url').'?'
                );
            }

            $message = $response->json('message') ?? [];
            $toolCalls = $message['tool_calls'] ?? null;

            if (empty($toolCalls)) {
                return $message['content'] ?? '';
            }

            $messages[] = [
                'role' => 'assistant',
                'content' => $message['content'] ?? '',
                'tool_calls' => $toolCalls,
            ];

            foreach ($toolCalls as $call) {
                $name = $call['function']['name'] ?? '';
                $args = $call['function']['arguments'] ?? [];

                if (is_string($args)) {
                    $args = json_decode($args, true) ?? [];
                }

                $messages[] = [
                    'role' => 'tool',
                    'content' => $this->tools->execute($name, $args),
                ];
            }
        }

        // Fallback after max iterations: one final call without tools
        $response = Http::timeout(120)
            ->post(config('ollama.url').'/api/chat', [
                'model' => $model,
                'messages' => $messages,
                'stream' => false,
                'options' => ['temperature' => 0],
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
                    'model' => Setting::getAiModel() ?? config('ollama.model'),
                    'messages' => $extractPrompt,
                    'stream' => false,
                    'options' => ['temperature' => 0],
                ]);

            $raw = trim($response->json('message.content') ?? '[]');

            // Strip markdown code fences if present
            $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw) ?? $raw;
            $raw = preg_replace('/\s*```$/', '', $raw) ?? $raw;

            $decoded = json_decode($raw, true);

            if (! is_array($decoded)) {
                return [];
            }

            return array_values(array_filter($decoded, fn ($v) => is_string($v) && strlen($v) > 3));
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Send an image to the configured vision model and return parsed JSON.
     *
     * Images are downscaled to max 1920px wide before sending to stay within
     * the model's context window (PS5 screenshots are 3840x2160 by default).
     *
     * @return array<string, mixed>
     */
    public function analyzeImage(string $imagePath, string $prompt): array
    {
        $base64 = $this->imageToBase64($imagePath);

        $response = Http::timeout(180)
            ->post(config('ollama.url').'/api/chat', [
                'model' => config('ollama.vision_model'),
                'stream' => false,
                'format' => 'json',
                'options' => ['temperature' => 0, 'num_ctx' => 8192],
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                        'images' => [$base64],
                    ],
                ],
            ]);

        if ($response->failed()) {
            $model = config('ollama.vision_model');
            $body = $response->json('error') ?? $response->body();
            $hint = $response->status() === 404
                ? "Model \"{$model}\" not found. Run: ollama pull {$model}"
                : $body;

            throw new RuntimeException('Ollama returned HTTP '.$response->status().'. '.$hint);
        }

        $content = $response->json('message.content') ?? '';

        if (is_array($content)) {
            return $content;
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Ollama vision response was not valid JSON: '.$content);
        }

        return $decoded;
    }

    /**
     * Resize the image to max 1920px wide and return as base64.
     * Falls back to the original bytes when GD is unavailable; num_ctx:8192
     * is enough headroom for even unresized PS5 4K screenshots (≈4108 tokens).
     */
    private function imageToBase64(string $imagePath): string
    {
        $raw = (string) file_get_contents($imagePath);

        if (! \extension_loaded('gd')) {
            return base64_encode($raw);
        }

        $info = \getimagesize($imagePath);

        if ($info === false || $info[0] <= 1920) {
            return base64_encode($raw);
        }

        $srcWidth = $info[0];
        $srcHeight = $info[1];
        $dstWidth = 1920;
        $dstHeight = max(1, (int) round($srcHeight * (1920 / $srcWidth)));

        $src = \imagecreatefromstring($raw);

        if ($src === false) {
            return base64_encode($raw);
        }

        $dst = \imagecreatetruecolor($dstWidth, $dstHeight);

        if ($dst === false) {
            \imagedestroy($src);

            return base64_encode($raw);
        }

        \imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        \imagedestroy($src);

        \ob_start();
        \imagejpeg($dst, null, 85);
        $jpeg = \ob_get_clean();
        \imagedestroy($dst);

        return base64_encode((string) $jpeg);
    }

    /** @param array<int, array{role: string, content: string}> $messages */
    public function chat(array $messages): string
    {
        $model = Setting::getAiModel() ?? config('ollama.model');

        $response = Http::timeout(120)
            ->post(config('ollama.url').'/api/chat', [
                'model' => $model,
                'messages' => $messages,
                'stream' => false,
                'options' => [
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
