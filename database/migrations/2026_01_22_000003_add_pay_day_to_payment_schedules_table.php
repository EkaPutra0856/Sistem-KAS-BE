<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_schedules', function (Blueprint $table) {
            $table->unsignedTinyInteger('pay_day_of_week')->default(5)->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('payment_schedules', function (Blueprint $table) {
            $table->dropColumn('pay_day_of_week');
        });
    }
};
