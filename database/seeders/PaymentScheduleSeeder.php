<?php

namespace Database\Seeders;

use App\Models\PaymentSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentScheduleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $schedules = [
            [
                'label' => 'Iuran Mingguan A',
                'start_date' => now()->startOfMonth()->toDateString(),
                'pay_day_of_week' => 7, // Sunday
                'created_by' => null,
                'active' => true,
            ],
            [
                'label' => 'Iuran Bulanan B',
                'start_date' => now()->subMonth()->toDateString(),
                'pay_day_of_week' => 1, // Monday
                'created_by' => null,
                'active' => true,
            ],
        ];

        foreach ($schedules as $s) {
            PaymentSchedule::updateOrCreate(
                ['label' => $s['label']],
                $s
            );
        }
    }
}
