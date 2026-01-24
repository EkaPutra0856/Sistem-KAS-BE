<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Payment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\PaymentScheduleSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PaymentScheduleSeeder::class);
        $users = [
            [
                'name' => 'Demo User',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => 'Demo Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
            [
                'name' => 'Demo Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password'),
                'role' => 'super-admin',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user,
            );
        }

        $member = User::where('email', 'user@example.com')->first();
        $admin = User::where('email', 'admin@example.com')->first();

        if ($member && $admin) {
            Payment::updateOrCreate(
                ['user_id' => $member->id, 'week_label' => 'Minggu 2', 'status' => 'approved'],
                [
                    'amount' => 50000,
                    'method' => 'QRIS',
                    'recorded_by' => $admin->id,
                    'approved_by' => $admin->id,
                    'approved_at' => now()->subWeek(),
                    'due_date' => now()->subDays(10)->toDateString(),
                ],
            );

            Payment::updateOrCreate(
                ['user_id' => $member->id, 'week_label' => 'Minggu 3', 'status' => 'pending'],
                [
                    'amount' => 50000,
                    'method' => 'Transfer',
                    'due_date' => now()->addDays(3)->toDateString(),
                ],
            );

            Payment::updateOrCreate(
                ['user_id' => $member->id, 'week_label' => 'Minggu 4', 'status' => 'pending'],
                [
                    'amount' => 50000,
                    'method' => 'QRIS',
                    'due_date' => now()->addDays(10)->toDateString(),
                ],
            );
        }
    }
}
