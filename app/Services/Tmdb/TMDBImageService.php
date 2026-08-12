<?php

declare(strict_types=1);

namespace App\Services\Tmdb;

final class TMDBImageService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('tmdb.image_base_url');
    }

    public function posterUrl(?string $path, string $size = 'medium'): ?string
    {
        if ($path === null) {
            return null;
        }

        $sizeCode = config('tmdb.poster_sizes')[$size] ?? config('tmdb.poster_sizes.medium');

        return $this->baseUrl.$sizeCode.$path;
    }

    public function backdropUrl(?string $path, string $size = 'large'): ?string
    {
        if ($path === null) {
            return null;
        }

        $sizeCode = config('tmdb.backdrop_sizes')[$size] ?? config('tmdb.backdrop_sizes.large');

        return $this->baseUrl.$sizeCode.$path;
    }

    public function profileUrl(?string $path, string $size = 'medium'): ?string
    {
        if ($path === null) {
            return null;
        }

        $sizeCode = config('tmdb.profile_sizes')[$size] ?? config('tmdb.profile_sizes.medium');

        return $this->baseUrl.$sizeCode.$path;
    }
}
