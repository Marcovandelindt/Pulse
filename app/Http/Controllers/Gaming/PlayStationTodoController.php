<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gaming\StorePlayStationTodoRequest;
use App\Models\PlayStationGame;
use App\Models\PlayStationGameTodo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

final class PlayStationTodoController extends Controller
{
    public function store(StorePlayStationTodoRequest $request, PlayStationGame $playStationGame): JsonResponse
    {
        $todo = $playStationGame->todos()->create([
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'order' => $playStationGame->todos()->whereNull('completed_at')->count(),
        ]);

        return response()->json(['todo' => $this->format($todo)], 201);
    }

    public function update(StorePlayStationTodoRequest $request, PlayStationGameTodo $playStationGameTodo): JsonResponse
    {
        $playStationGameTodo->update([
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
        ]);

        return response()->json(['todo' => $this->format($playStationGameTodo)]);
    }

    public function toggle(PlayStationGameTodo $playStationGameTodo): JsonResponse
    {
        $playStationGameTodo->update([
            'completed_at' => $playStationGameTodo->isCompleted() ? null : Carbon::now(),
        ]);

        return response()->json(['todo' => $this->format($playStationGameTodo)]);
    }

    public function destroy(PlayStationGameTodo $playStationGameTodo): JsonResponse
    {
        $playStationGameTodo->delete();

        return response()->json(['deleted' => true]);
    }

    /** @return array<string, mixed> */
    private function format(PlayStationGameTodo $todo): array
    {
        return [
            'id' => $todo->id,
            'title' => $todo->title,
            'body' => $todo->body,
            'completed_at' => $todo->completed_at?->toISOString(),
        ];
    }
}
