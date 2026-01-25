<?php

namespace App\Http\Controllers;

use App\Models\FeeInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeeInfoController extends Controller
{
    private function ensureAuthenticated(Request $request): ?JsonResponse
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return null;
    }

    private function ensureAdmin(Request $request): ?JsonResponse
    {
        $actor = $request->user();
        if (! $actor || ! in_array($actor->role, ['admin', 'super-admin'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return null;
    }

    public function show(Request $request): JsonResponse
    {
        if ($resp = $this->ensureAuthenticated($request)) return $resp;

        $info = FeeInfo::first();
        if (! $info) {
            $info = FeeInfo::create([
                'title' => 'Iuran ini dipakai untuk apa?',
                'description' => 'Dana kas mingguan Rp50.000 dipakai untuk kebersihan lingkungan, listrik sekretariat, kas darurat, dan bantuan sosial komunitas.',
                'amount_per_week' => 50000,
                'badge_1' => 'Nominal tetap: Rp50.000/minggu',
                'badge_2' => 'Jatuh tempo sesuai jadwal admin',
                'badge_3' => 'Gunakan QRIS/transfer untuk verifikasi cepat',
            ]);
        }

        return response()->json(['data' => $info]);
    }

    public function update(Request $request): JsonResponse
    {
        if ($resp = $this->ensureAdmin($request)) return $resp;

        $validated = $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'amount_per_week' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'badge_1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'badge_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'badge_3' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $info = FeeInfo::first();
        if (! $info) {
            $info = new FeeInfo();
        }

        $info->fill($validated);
        $info->updated_by = $request->user()->id;
        $info->save();

        return response()->json(['data' => $info]);
    }
}
