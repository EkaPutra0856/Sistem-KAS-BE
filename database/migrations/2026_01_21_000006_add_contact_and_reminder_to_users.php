<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->unique()->after('email');
            $table->boolean('auto_reminder_enabled')->default(true)->after('remember_token');
            $table->boolean('reminder_email_enabled')->default(true)->after('auto_reminder_enabled');
            $table->boolean('reminder_whatsapp_enabled')->default(true)->after('reminder_email_enabled');
            $table->timestamp('whatsapp_verified_at')->nullable()->after('email_verified_at');
            $table->string('whatsapp_verification_code', 12)->nullable()->after('whatsapp_verified_at');
            $table->string('email_verification_code', 12)->nullable()->after('whatsapp_verification_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'auto_reminder_enabled',
                'reminder_email_enabled',
                'reminder_whatsapp_enabled',
                'whatsapp_verified_at',
                'whatsapp_verification_code',
                'email_verification_code',
            ]);
        });
    }
};
