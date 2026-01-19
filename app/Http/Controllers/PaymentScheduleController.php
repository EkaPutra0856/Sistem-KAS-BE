<?php

namespace App\Http\Controllers;

use App\Models\PaymentSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentScheduleController extends Controller
{
    private function ensureAdmin(Request $request): ?JsonResponse
    {
        $actor = $request->user();
        if (! $actor || ! in_array($actor->role, ['admin', 'super-admin'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return null;
    }

    public function index(Request $request): JsonResponse
    {
        // allow any authenticated user to read schedules so frontend calendars can render
        $actor = $request->user();
        if (! $actor) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $schedules = PaymentSchedule::query()->where('active', true)->orderBy('start_date', 'asc')->get();

        return response()->json(['data' => $schedules]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($resp = $this->ensureAdmin($request)) return $resp;

        $validated = $request->validate([
            'label' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $actor = $request->user();

        $sched = PaymentSchedule::create([
            'label' => $validated['label'] ?? null,
            'start_date' => $validated['start_date'],
            'created_by' => $actor->id,
            'active' => $validated['active'] ?? true,
            'end_date' => $validated['end_date'] ?? null,
        ]);

        return response()->json(['data' => $sched], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if ($resp = $this->ensureAdmin($request)) return $resp;

        $sched = PaymentSchedule::find($id);
        if (! $sched) return response()->json(['message' => 'Not found'], 404);

        $validated = $request->validate([
            'label' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $sched->fill($validated);
        $sched->save();

        return response()->json(['data' => $sched]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($resp = $this->ensureAdmin($request)) return $resp;

        $sched = PaymentSchedule::find($id);
        if (! $sched) return response()->json(['message' => 'Not found'], 404);

        $sched->delete();

        return response()->json([], 204);
    }
}
