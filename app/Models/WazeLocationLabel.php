<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $label
 * @property string $match_name
 */
final class WazeLocationLabel extends Model
{
    protected $fillable = ['label', 'match_name'];

    public function matches(?string $locationName): bool
    {
        if ($locationName === null) {
            return false;
        }

        return str_contains(mb_strtolower($locationName), mb_strtolower($this->match_name));
    }
}
