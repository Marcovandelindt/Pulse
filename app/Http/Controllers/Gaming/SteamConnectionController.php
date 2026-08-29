<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\Steam\TestSteamConnection;
use App\Http\Controllers\Controller;
use App\Models\SteamAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SteamConnectionController extends Controller
{
    public function store(Request $request, TestSteamConnection $action): JsonResponse
    {
        $accountId = $request->input('account_id');
        $account   = $accountId
            ? SteamAccount::findOrFail($accountId)
            : SteamAccount::active();

        if (! $account) {
            return response()->json(['success' => false, 'error' => 'No Steam account configured.'], 422);
        }

        $result = $action->handle($account);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
