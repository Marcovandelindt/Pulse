<?php

declare(strict_types=1);

return [
    'url'   => env('OLLAMA_URL', 'http://localhost:11434'),
    'model' => env('OLLAMA_MODEL', 'llama3.1:8b'),
];
