<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    private const METHODS = ['QRIS', 'Transfer', 'Tunai'];
    private const STATUSES = ['pending', 'approved', 'rejected'];

    private function serializePayment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'user' => $payment->user?->only(['id', 'name', 'email', 'role']),
            'recorded_by' => $payment->recordedBy?->only(['id', 'name', 'email']),
            'approved_by' => $payment->approvedBy?->only(['id', 'name', 'email']),
            'week_label' => $payment->week_label,
            'due_date' => $payment->due_date?->toDateString(),
            'amount' => $payment->amount,
            'method' => $payment->method,
            'status' => $payment->status,
            'proof_url' => $payment->proof_path ? Storage::disk('public')->url($payment->proof_path) : null,
            'schedule' => $payment->schedule ? [
                'id' => $payment->schedule->id,
                'label' => $payment->schedule->label,
                'start_date' => $payment->schedule->start_date?->toDateString(),
                'end_date' => $payment->schedule->end_date?->toDateString(),
            ] : null,
            'approved_at' => $payment->approved_at,
            'created_at' => $payment->created_at,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();

        $query = Payment::query()->with(['user:id,name,email,role', 'recordedBy:id,name,email', 'approvedBy:id,name,email']);

        if ($actor->role === 'user') {
            $query->where('user_id', $actor->id);
        } else {
            // admin/super-admin can filter by user_id or status
            if ($request->filled('user_id')) {
                $query->where('user_id', (int) $request->input('user_id'));
            }
            if ($request->filled('status')) {
                $status = (string) $request->input('status');
                if (in_array($status, self::STATUSES, true)) {
                    $query->where('status', $status);
                }
            }
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($builder) use ($q) {
                $builder->where('week_label', 'like', "%{$q}%");
            });
        }

        $perPage = (int) $request->query('per_page', 10);
        $payments = $query->orderByDesc('created_at')->paginate(max(1, $perPage));

        $data = collect($payments->items())->map(fn (Payment $p) => $this->serializePayment($p));

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $actor = $request->user();

        if ($actor->role === 'super-admin') {
            return response()->json(['message' => 'Super admin tidak dapat membuat pembayaran'], 403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1000'],
            'method' => ['required', 'in:' . implode(',', self::METHODS)],
            'week_label' => ['required', 'string', 'max:100'],
            'due_date' => ['sometimes', 'date'],
            'schedule_id' => ['sometimes', 'integer', 'exists:payment_schedules,id'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'proof' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $targetUserId = $actor->role === 'user'
            ? $actor->id
            : ($validated['user_id'] ?? null);

        if (! $targetUserId) {
            return response()->json(['message' => 'user_id diperlukan untuk bendahara'], 422);
        }

        $targetUser = User::find($targetUserId);
        if (! $targetUser) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        if ($actor->role === 'user' && $targetUser->id !== $actor->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($actor->role === 'admin' && $targetUser->role === 'super-admin') {
            return response()->json(['message' => 'Tidak dapat mencatat untuk super admin'], 403);
        }

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $file = $request->file('proof');
            $filename = 'proofs/' . Str::random(16) . '.' . $file->getClientOriginalExtension();
            $proofPath = $file->storeAs('proofs', basename($filename), 'public');
        }

        $status = $actor->role === 'admin' ? 'approved' : 'pending';

        $payment = Payment::create([
            'user_id' => $targetUser->id,
            'recorded_by' => $actor->role === 'admin' ? $actor->id : null,
            'schedule_id' => $validated['schedule_id'] ?? null,
            'week_label' => $validated['week_label'],
            'due_date' => $validated['due_date'] ?? null,
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'status' => $status,
            'proof_path' => $proofPath,
            'approved_at' => $status === 'approved' ? now() : null,
            'approved_by' => $status === 'approved' ? $actor->id : null,
        ]);

        return response()->json(['data' => $this->serializePayment($payment)], 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();

        if (! in_array($actor->role, ['admin', 'super-admin'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected,pending'],
        ]);

        $payment = Payment::find($id);

        if (! $payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $payment->status = $validated['status'];
        $payment->approved_by = in_array($validated['status'], ['approved', 'rejected'], true) ? $actor->id : null;
        $payment->approved_at = $validated['status'] === 'approved' ? now() : null;
        $payment->save();

        return response()->json(['data' => $this->serializePayment($payment)]);
    }
}
