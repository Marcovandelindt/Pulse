<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Link extends Model
{
    protected $fillable = [
        'url',
        'title',
        'description',
    ];

    public function domain(): string
    {
        $host = parse_url($this->url, PHP_URL_HOST);

        return is_string($host) ? $host : $this->url;
    }

    public function faviconUrl(): string
    {
        $domain = $this->domain();

        return "https://www.google.com/s2/favicons?domain={$domain}&sz=32";
    }
}
