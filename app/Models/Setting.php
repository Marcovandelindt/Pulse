<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember('setting.'.$key, 3600, fn () => self::where('key', $key)->value('value') ?? $default);
    }

    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('setting.'.$key);
    }

    public static function remove(string $key): void
    {
        self::where('key', $key)->delete();
        Cache::forget('setting.'.$key);
    }

    public static function getPsnRefreshToken(): ?string
    {
        return self::get('psn_refresh_token');
    }

    public static function storePsnRefreshToken(string $token): void
    {
        self::set('psn_refresh_token', $token);
    }

    public static function clearPsnRefreshToken(): void
    {
        self::remove('psn_refresh_token');
    }

    public static function getAiPersonality(): ?string
    {
        return self::get('ai_personality');
    }

    public static function setAiPersonality(?string $value): void
    {
        if ($value) {
            self::set('ai_personality', $value);
        } else {
            self::remove('ai_personality');
        }
    }

    public static function getAiModel(): ?string
    {
        return self::get('ai_model');
    }

    public static function setAiModel(?string $value): void
    {
        if ($value) {
            self::set('ai_model', $value);
        } else {
            self::remove('ai_model');
        }
    }

    public static function getSpotifyCredentials(): array
    {
        return [
            'access_token' => self::get('spotify_access_token'),
            'refresh_token' => self::get('spotify_refresh_token'),
            'expires_at' => self::get('spotify_token_expires_at'),
            'spotify_id' => self::get('spotify_id'),
        ];
    }

    public static function storeSpotifyCredentials(array $credentials): void
    {
        self::set('spotify_access_token', $credentials['access_token']);
        self::set('spotify_refresh_token', $credentials['refresh_token']);
        self::set('spotify_token_expires_at', $credentials['expires_at']);
        self::set('spotify_id', $credentials['spotify_id']);
    }
}
