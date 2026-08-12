<?php

declare(strict_types=1);

namespace App\Services\Tmdb;

use Illuminate\Support\Facades\Http;

final class TMDBClient
{
    private string $baseUrl;

    private string $apiKey;

    private string $language;

    private string $region;

    public function __construct()
    {
        $this->baseUrl = config('tmdb.base_url');
        $this->apiKey = config('tmdb.api_key');
        $this->language = config('tmdb.language');
        $this->region = config('tmdb.region');
    }

    public function get(string $endpoint, array $params = []): array
    {
        $response = Http::get($this->baseUrl.$endpoint, array_merge([
            'api_key' => $this->apiKey,
            'language' => $this->language,
            'region' => $this->region,
        ], $params));

        return $response->json() ?? [];
    }

    public function getWithLanguage(string $endpoint, string $language, array $params = []): array
    {
        $response = Http::get($this->baseUrl.$endpoint, array_merge([
            'api_key' => $this->apiKey,
            'language' => $language,
        ], $params));

        return $response->json() ?? [];
    }
}
