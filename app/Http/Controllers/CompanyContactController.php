<?php

namespace App\Http\Controllers;

use App\Models\CompanyContact;
use App\Models\CompanyContactHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyContactController extends Controller
{
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
        if ($resp = $this->ensureAdmin($request)) return $resp;

        $contact = CompanyContact::query()->latest('updated_at')->first();

        return response()->json([
            'data' => $contact,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        if ($resp = $this->ensureAdmin($request)) return $resp;

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $actor = $request->user();

        $contact = CompanyContact::query()->first();
        if (! $contact) {
            $contact = new CompanyContact();
        }

        $contact->email = $validated['email'];
        $contact->phone = $validated['phone'];
        $contact->updated_by = $actor->id;
        $contact->save();

        CompanyContactHistory::create([
            'email' => $contact->email,
            'phone' => $contact->phone,
            'changed_by' => $actor->id,
            'changed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Kontak perusahaan diperbarui',
            'data' => $contact,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        if ($resp = $this->ensureAdmin($request)) return $resp;

        $history = CompanyContactHistory::query()
            ->orderByDesc('changed_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $history,
        ]);
    }
}
