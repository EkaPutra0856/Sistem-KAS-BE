<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    private int $weeklyTarget;
    private int $weeksPerMonth;
    private string $reminderText;

    public function __construct()
    {
        $this->weeklyTarget = (int) config('kas.weekly_target', 50000);
        $this->weeksPerMonth = (int) config('kas.weeks_per_month', 4);
        $this->reminderText = (string) config('kas.reminder_text', 'Setiap Jumat, 07:00');
    }

    private function ensureRole(Request $request, array $roles): ?JsonResponse
    {
        $actor = $request->user();
        if (! $actor || ! in_array($actor->role, $roles, true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return null;
    }

    private function buildMonthlyTrend(Collection $payments, int $months = 6): array
    {
        $buckets = [];
        $start = now()->startOfMonth();
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = $start->copy()->subMonths($i);
            $key = $month->format('Y-m');
            $buckets[$key] = [
                'name' => $month->format('M'),
                'total' => 0,
                'communities' => 0,
                'admins' => 0,
            ];
        }

        foreach ($payments as $payment) {
            if (! $payment->created_at) {
                continue;
            }
            $key = $payment->created_at->format('Y-m');
            if (array_key_exists($key, $buckets)) {
                $buckets[$key]['total'] += (int) $payment->amount;
            }
        }

        return array_values($buckets);
    }

    public function user(Request $request): JsonResponse
    {
        if ($resp = $this->ensureRole($request, ['user', 'admin', 'super-admin'])) {
            return $resp;
        }

        $actor = $request->user();

        $payments = Payment::query()
            ->with('schedule')
            ->where('user_id', $actor->id)
            ->orderByDesc('created_at')
            ->get();

        $approved = $payments->where('status', 'approved');
        $currentMonthKey = now()->format('Y-m');

        $weeklyPayments = $payments
            ->groupBy('week_label')
            ->sortByDesc(fn (Collection $group) => optional($group->first()?->created_at)->getTimestamp())
            ->take(8)
            ->reverse()
            ->map(function (Collection $group, string $label) {
                return [
                    'name' => $label,
                    'paid' => (int) $group->where('status', 'approved')->sum('amount'),
                    'due' => $this->weeklyTarget,
                ];
            })
            ->values();

        $monthlyTrend = collect($this->buildMonthlyTrend($approved, 6));

        $activeBills = $payments
            ->filter(fn ($p) => in_array($p->status, ['pending', 'rejected'], true))
            ->sortBy(function ($p) {
                return optional($p->due_date)->timestamp ?? PHP_INT_MAX;
            })
            ->take(5)
            ->map(function (Payment $p) {
                return [
                    'id' => $p->id,
                    'label' => $p->week_label,
                    'due' => $p->due_date?->format('d M Y'),
                    'amount' => (int) $p->amount,
                    'status' => $p->status === 'rejected' ? 'Ditolak' : ($p->status === 'approved' ? 'Lunas' : 'Belum dibayar'),
                ];
            })
            ->values();

        $recentActivities = $payments
            ->sortByDesc('created_at')
            ->take(6)
            ->map(function (Payment $p) {
                return [
                    'title' => $p->week_label,
                    'amount' => (int) $p->amount,
                    'time' => $p->created_at?->format('d M Y, H:i'),
                    'type' => $p->status === 'approved' ? 'in' : 'note',
                    'status' => $p->status,
                ];
            })
            ->values();

        $totalPaidThisMonth = $approved->filter(fn ($p) => $p->created_at?->format('Y-m') === $currentMonthKey)->sum('amount');
        $paidWeeksThisMonth = $approved->filter(fn ($p) => $p->created_at?->format('Y-m') === $currentMonthKey)->count();

        $firstActiveBill = $activeBills->first();

        $summary = [
            'current_bill_amount' => is_array($firstActiveBill) ? ($firstActiveBill['amount'] ?? $this->weeklyTarget) : $this->weeklyTarget,
            'current_bill_due' => is_array($firstActiveBill) ? ($firstActiveBill['due'] ?? null) : null,
            'total_paid_this_month' => (int) $totalPaidThisMonth,
            'paid_weeks_this_month' => $paidWeeksThisMonth,
            'month_target' => $this->weeklyTarget * $this->weeksPerMonth,
            'collected_total' => (int) $approved->sum('amount'),
            'reminder_text' => $this->reminderText,
            'auto_reminder_enabled' => (bool) $actor->auto_reminder_enabled,
            'reminder_email_enabled' => (bool) $actor->reminder_email_enabled,
            'reminder_whatsapp_enabled' => (bool) $actor->reminder_whatsapp_enabled,
            'whatsapp_verified' => (bool) $actor->whatsapp_verified_at,
            'email_verified' => (bool) $actor->email_verified_at,
            'has_email' => ! empty($actor->email),
            'has_phone' => ! empty($actor->phone),
        ];

        return response()->json([
            'data' => [
                'summary' => $summary,
                'weekly_payments' => $weeklyPayments,
                'monthly_trend' => $monthlyTrend,
                'active_bills' => $activeBills,
                'recent_activities' => $recentActivities,
            ],
        ]);
    }

    public function admin(Request $request): JsonResponse
    {
        if ($resp = $this->ensureRole($request, ['admin', 'super-admin'])) {
            return $resp;
        }

        $users = User::query()->where('role', 'user')->with('latestPayment')->get();
        $payments = Payment::query()->with('user:id,name,email')->orderByDesc('created_at')->get();
        $approved = $payments->where('status', 'approved');
        $pendingApprovals = $payments->where('status', 'pending');
        $currentSchedule = PaymentSchedule::query()->orderByDesc('start_date')->first();

        $totalCollectedThisMonth = $approved
            ->filter(fn ($p) => $p->created_at?->format('Y-m') === now()->format('Y-m'))
            ->sum('amount');

        $monthlyTarget = $users->count() * $this->weeklyTarget * $this->weeksPerMonth;

        $setoranWeekCount = $approved
            ->filter(function ($p) use ($currentSchedule) {
                if ($currentSchedule) {
                    return $p->schedule_id === $currentSchedule->id;
                }
                return $p->created_at?->isCurrentWeek();
            })
            ->count();

        $weeklyCollections = $payments
            ->groupBy('week_label')
            ->sortByDesc(fn (Collection $group) => optional($group->first()?->created_at)->getTimestamp())
            ->take(12)
            ->reverse()
            ->map(function (Collection $group, string $label) use ($users) {
                return [
                    'name' => $label,
                    'paid' => $group->where('status', 'approved')->count(),
                    'target' => $users->count(),
                ];
            })
            ->values();

        $monthlyTrend = collect($this->buildMonthlyTrend($approved, 6));

        $pendingList = $pendingApprovals
            ->take(20)
            ->map(function (Payment $p) {
                return [
                    'id' => $p->id,
                    'amount' => (int) $p->amount,
                    'week_label' => $p->week_label,
                    'method' => $p->method,
                    'user' => $p->user?->only(['id', 'name', 'email']),
                    'created_at' => $p->created_at,
                ];
            })
            ->values();

        $arrearsUsers = $users
            ->filter(fn (User $u) => $u->latestPayment?->status !== 'approved')
            ->take(50)
            ->values()
            ->map(function (User $u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'last_payment_week' => $u->latestPayment?->week_label,
                    'last_payment_amount' => $u->latestPayment?->amount,
                    'last_payment_status' => $u->latestPayment?->status,
                ];
            });

        $reminders = [
            [
                'title' => 'Verifikasi bukti pembayaran',
                'value' => $pendingApprovals->count(),
                'kind' => 'approval',
            ],
            [
                'title' => 'Follow-up tunggakan',
                'value' => $arrearsUsers->count(),
                'kind' => 'collection',
            ],
            [
                'title' => 'Pastikan jadwal aktif',
                'value' => $currentSchedule?->start_date?->format('d M Y'),
                'kind' => 'schedule',
            ],
        ];

        $daysToNextDue = null;
        if ($currentSchedule && $currentSchedule->end_date) {
            $daysToNextDue = now()->diffInDays(Carbon::parse($currentSchedule->end_date), false);
        }

        return response()->json([
            'data' => [
                'summary' => [
                    'total_collected_this_month' => (int) $totalCollectedThisMonth,
                    'monthly_target' => $monthlyTarget,
                    'setoran_week_count' => $setoranWeekCount,
                    'member_count' => $users->count(),
                    'pending_approval_count' => $pendingApprovals->count(),
                    'arrears_count' => $arrearsUsers->count(),
                    'current_schedule' => $currentSchedule,
                ],
                'weekly_collections' => $weeklyCollections,
                'monthly_trend' => $monthlyTrend,
                'pending_approvals' => $pendingList,
                'arrears_users' => $arrearsUsers->values(),
                'reminders' => $reminders,
                'days_to_next_due' => $daysToNextDue,
            ],
        ]);
    }

    public function superAdmin(Request $request): JsonResponse
    {
        if ($resp = $this->ensureRole($request, ['super-admin'])) {
            return $resp;
        }

        $admins = User::query()->where('role', 'admin')->get();
        $users = User::query()->get();
        $schedules = PaymentSchedule::query()->get();
        $payments = Payment::query()->with('user:id,name,email')->get();
        $approved = $payments->where('status', 'approved');
        $pending = $payments->where('status', 'pending');

        $totalKas = $approved->sum('amount');
        $issuesCount = $pending->count();

        $monthBuckets = collect($this->buildMonthlyTrend($approved, 6))->keyBy(fn ($item) => $item['name']);

        // Enrich bucket with admin and community trends
        foreach ($schedules as $sched) {
            if (! $sched->start_date) {
                continue;
            }
            $key = $sched->start_date->format('M');
            if ($monthBuckets->has($key)) {
                $item = $monthBuckets->get($key);
                $item['communities'] = ($item['communities'] ?? 0) + 1;
                $monthBuckets[$key] = $item;
            }
        }

        foreach ($admins as $admin) {
            if (! $admin->created_at) {
                continue;
            }
            $key = $admin->created_at->format('M');
            if ($monthBuckets->has($key)) {
                $item = $monthBuckets->get($key);
                $item['admins'] = ($item['admins'] ?? 0) + 1;
                $monthBuckets[$key] = $item;
            }
        }

        $riskData = [
            [
                'name' => 'Telat >7 hari',
                'count' => $pending->filter(fn ($p) => $p->created_at?->lt(now()->subDays(7)))->count(),
            ],
            [
                'name' => 'Belum verifikasi',
                'count' => $pending->count(),
            ],
            [
                'name' => 'Saldo minimum',
                'count' => $users
                    ->filter(function (User $u) {
                        $approved = $u->payments()->where('status', 'approved')->whereMonth('created_at', now()->month)->sum('amount');
                        return $approved < ($this->weeklyTarget * $this->weeksPerMonth);
                    })
                    ->count(),
            ],
        ];

        $activeAdmins = $admins
            ->sortByDesc('created_at')
            ->take(10)
            ->map(function (User $admin) {
                return [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'role' => 'Admin',
                    'status' => 'Active',
                    'email' => $admin->email,
                ];
            })
            ->values();

        $auditLogs = $payments
            ->sortByDesc('created_at')
            ->take(12)
            ->map(function (Payment $p) {
                return [
                    'event' => 'Pembayaran ' . $p->week_label,
                    'user' => $p->user?->name ?? 'Pengguna',
                    'time' => $p->created_at?->diffForHumans(),
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'metrics' => [
                    'communities' => max(1, $schedules->count()),
                    'admins' => $admins->count(),
                    'total_kas' => (int) $totalKas,
                    'issues' => $issuesCount,
                ],
                'org_trend' => $monthBuckets->values(),
                'risk_data' => $riskData,
                'active_admins' => $activeAdmins,
                'audit_logs' => $auditLogs,
            ],
        ]);
    }
}
