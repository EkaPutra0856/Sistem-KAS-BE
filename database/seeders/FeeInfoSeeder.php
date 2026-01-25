<?php

namespace Database\Seeders;

use App\Models\FeeInfo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeeInfoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        FeeInfo::updateOrCreate(
            ['id' => 1],
            [
                'title' => 'Iuran ini dipakai untuk apa?',
                'description' => 'Dana kas mingguan Rp50.000 dipakai untuk kebersihan lingkungan, listrik sekretariat, kas darurat, dan bantuan sosial komunitas.',
                'amount_per_week' => 50000,
                'badge_1' => 'Nominal tetap: Rp50.000/minggu',
                'badge_2' => 'Jatuh tempo sesuai jadwal admin',
                'badge_3' => 'Gunakan QRIS/transfer untuk verifikasi cepat',
                'updated_by' => null,
            ]
        );
    }
}
