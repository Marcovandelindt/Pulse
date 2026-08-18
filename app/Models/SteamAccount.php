<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SteamAccount extends Model
{
    protected $fillable = ['label', 'steam_id', 'api_key', 'is_active'];

    protected function casts(): array
    {
        return [
            'api_key'   => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function games(): HasMany
    {
        return $this->hasMany(SteamGame::class);
    }

    public function activate(): void
    {
        static::query()->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
