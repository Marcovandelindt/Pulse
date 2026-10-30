<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\PlayStation\Cod\BuildCodFingerprint;
use App\Actions\PlayStation\Cod\CodMatchData;
use App\Enums\CodMatchStatus;
use App\Enums\CodResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gaming\UpdateCodReviewRequest;
use App\Models\CodMap;
use App\Models\CodMatch;
use App\Models\CodMode;
use App\Models\CodScreenshot;
use App\Models\CodWeapon;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CodReviewController extends Controller
{
    public function __construct(
        private readonly BuildCodFingerprint $fingerprinter,
    ) {}

    public function index(): View
    {
        $matches = CodMatch::with(['map', 'mode', 'topWeapon', 'screenshots'])
            ->where('status', CodMatchStatus::PendingReview)
            ->orderByDesc('id')
            ->get();

        return view('pages.playstation.cod.review', compact('matches'));
    }

    public function screenshot(CodScreenshot $screenshot): BinaryFileResponse
    {
        $path = storage_path('app/private/cod-screenshots/'.$screenshot->filename);

        return response()->file($path);
    }

    public function update(UpdateCodReviewRequest $request, CodMatch $codMatch): RedirectResponse
    {
        $v = $request->validated();

        $map = CodMap::firstOrCreate(['name' => $v['map']]);
        $mode = CodMode::firstOrCreate(['name' => $v['mode']]);
        $weapon = null;

        if (! empty($v['weapon_name'])) {
            $weapon = CodWeapon::firstOrCreate(
                ['name' => $v['weapon_name']],
                ['category' => $v['weapon_category'] ?? 'Unknown'],
            );
        }

        $matchData = new CodMatchData(
            map: $v['map'],
            mode: $v['mode'],
            result: CodResult::from($v['result']),
            teamScore: (int) $v['team_score'],
            opponentScore: (int) $v['opponent_score'],
            score: (int) $v['score'],
            kills: (int) $v['kills'],
            deaths: (int) $v['deaths'],
            kdRatio: (float) $v['kd_ratio'],
            objectiveScore: isset($v['objective_score']) ? (int) $v['objective_score'] : null,
            damage: (int) $v['damage'],
            weaponName: $v['weapon_name'] ?? null,
            weaponCategory: $v['weapon_category'] ?? null,
            weaponAccuracy: isset($v['weapon_accuracy']) ? (float) $v['weapon_accuracy'] : null,
            weaponKills: isset($v['weapon_kills']) ? (int) $v['weapon_kills'] : null,
            relativeTime: '',
            estimatedDate: Carbon::parse($v['estimated_date']),
        );

        $fingerprint = $this->fingerprinter->handle($matchData);

        $codMatch->update([
            'cod_map_id' => $map->id,
            'cod_mode_id' => $mode->id,
            'result' => CodResult::from($v['result']),
            'team_score' => $v['team_score'],
            'opponent_score' => $v['opponent_score'],
            'score' => $v['score'],
            'kills' => $v['kills'],
            'deaths' => $v['deaths'],
            'kd_ratio' => $v['kd_ratio'],
            'objective_score' => $v['objective_score'] ?? null,
            'damage' => $v['damage'],
            'top_weapon_id' => $weapon?->id,
            'weapon_accuracy' => $v['weapon_accuracy'] ?? null,
            'weapon_kills' => $v['weapon_kills'] ?? null,
            'estimated_date' => $v['estimated_date'],
            'fingerprint' => $fingerprint,
            'status' => CodMatchStatus::Confirmed,
        ]);

        return redirect()->route('playstation.cod.review')
            ->with('success', 'Match confirmed.');
    }

    public function destroy(CodMatch $codMatch): RedirectResponse
    {
        $codMatch->delete();

        return redirect()->route('playstation.cod.review')
            ->with('success', 'Match rejected and removed.');
    }
}
