<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DataUserController extends Controller
{
    /**
     * Ensure only admin or super-admin can access the action.
     */
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
        if ($resp = $this->ensureAdmin($request)) {
            return $resp;
        }

        $requestedRole = $request->query('role', 'user');

        // Admins may only manage regular users; super-admins can pick role filter
        $actor = $request->user();
        if ($actor->role === 'admin' && $requestedRole !== 'user') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! in_array($requestedRole, ['user', 'admin'], true)) {
            $requestedRole = 'user';
        }

        $perPage = (int) $request->query('per_page', 10);
        $q = trim((string) $request->query('q', ''));

        $query = User::query()
            ->select(['id', 'name', 'email', 'role', 'created_at'])
            ->where('role', $requestedRole)
            ->with(['latestPayment' => function ($q) {
                $q->select([
                    'payments.id',
                    'payments.user_id',
                    'payments.status',
                    'payments.amount',
                    'payments.week_label',
                    'payments.method',
                    'payments.created_at',
                ]);
            }]);

        if ($q !== '') {
            $query->where(function ($b) use ($q) {
                $b->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(max(1, $perPage));

        $data = collect($users->items())->map(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'last_payment_status' => $user->latestPayment?->status,
                'last_payment_week' => $user->latestPayment?->week_label,
                'last_payment_amount' => $user->latestPayment?->amount,
                'last_payment_method' => $user->latestPayment?->method,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if ($resp = $this->ensureAdmin($request)) {
            return $resp;
        }

        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->load(['latestPayment' => function ($q) {
            $q->select([
                'payments.id',
                'payments.user_id',
                'payments.status',
                'payments.amount',
                'payments.week_label',
                'payments.method',
                'payments.created_at',
            ]);
        }]);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'last_payment_status' => $user->latestPayment?->status,
                'last_payment_week' => $user->latestPayment?->week_label,
                'last_payment_amount' => $user->latestPayment?->amount,
                'last_payment_method' => $user->latestPayment?->method,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($resp = $this->ensureAdmin($request)) {
            return $resp;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:user,admin,super-admin'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['data' => $user], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if ($resp = $this->ensureAdmin($request)) {
            return $resp;
        }

        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['sometimes', 'required', 'in:user,admin,super-admin'],
        ]);

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }

        if (array_key_exists('role', $validated)) {
            $user->role = $validated['role'];
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json(['data' => $user]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($resp = $this->ensureAdmin($request)) {
            return $resp;
        }

        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot delete your own account'], 422);
        }

        $user->delete();

        return response()->json([], 204);
    }
}
