<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CodMatchStatus;
use App\Enums\CodResult;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $cod_map_id
 * @property int $cod_mode_id
 * @property CodResult $result
 * @property int $team_score
 * @property int $opponent_score
 * @property int $score
 * @property int $kills
 * @property int $deaths
 * @property string $kd_ratio
 * @property int|null $objective_score
 * @property int $damage
 * @property int|null $top_weapon_id
 * @property string|null $weapon_accuracy
 * @property int|null $weapon_kills
 * @property Carbon $estimated_date
 * @property string $fingerprint
 * @property CodMatchStatus $status
 * @property CodMap $map
 * @property CodMode $mode
 * @property CodWeapon|null $topWeapon
 */
final class CodMatch extends Model
{
    protected $fillable = [
        'cod_map_id',
        'cod_mode_id',
        'result',
        'team_score',
        'opponent_score',
        'score',
        'kills',
        'deaths',
        'kd_ratio',
        'objective_score',
        'damage',
        'top_weapon_id',
        'weapon_accuracy',
        'weapon_kills',
        'estimated_date',
        'fingerprint',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'result' => CodResult::class,
            'status' => CodMatchStatus::class,
            'estimated_date' => 'date',
            'kd_ratio' => 'decimal:2',
            'weapon_accuracy' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<CodMap, $this> */
    public function map(): BelongsTo
    {
        return $this->belongsTo(CodMap::class, 'cod_map_id');
    }

    /** @return BelongsTo<CodMode, $this> */
    public function mode(): BelongsTo
    {
        return $this->belongsTo(CodMode::class, 'cod_mode_id');
    }

    /** @return BelongsTo<CodWeapon, $this> */
    public function topWeapon(): BelongsTo
    {
        return $this->belongsTo(CodWeapon::class, 'top_weapon_id');
    }

    /** @return BelongsToMany<CodScreenshot, $this> */
    public function screenshots(): BelongsToMany
    {
        return $this->belongsToMany(CodScreenshot::class);
    }
}
