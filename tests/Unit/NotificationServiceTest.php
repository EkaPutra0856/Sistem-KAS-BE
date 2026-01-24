<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\NotificationService;
use App\Mail\ReminderMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_reminder_sends_enabled_channels(): void
    {
        Mail::fake();
        Log::spy();
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['messages' => []], 200),
        ]);

        config([
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456',
        ]);

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'whatsapp_verified_at' => now(),
            'phone' => '628123456789',
            'reminder_email_enabled' => true,
            'reminder_whatsapp_enabled' => true,
        ]);

        app(NotificationService::class)->sendReminder($user, 'Pengingat Kas', 'Segera setorkan kas mingguan.');

        Mail::assertSent(ReminderMail::class, function (ReminderMail $mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->title === 'Pengingat Kas'
                && $mail->bodyText === 'Segera setorkan kas mingguan.';
        });

        Http::assertSent(function ($request) use ($user) {
            return str_contains($request->url(), 'https://graph.facebook.com/')
                && data_get($request->data(), 'to') === $user->phone
                && data_get($request->data(), 'text.body') === "Pengingat Kas\n\nSegera setorkan kas mingguan.";
        });

        // WhatsApp is sent via HTTP client; we already asserted the HTTP request above.
    }

    public function test_send_reminder_skips_unverified_channels(): void
    {
        Mail::fake();
        Log::spy();
        Http::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
            'whatsapp_verified_at' => null,
            'phone' => '628123456789',
            'reminder_email_enabled' => true,
            'reminder_whatsapp_enabled' => true,
        ]);

        app(NotificationService::class)->sendReminder($user, 'Pengingat Kas', 'Segera setorkan kas mingguan.');

        Mail::assertNothingSent();
        Http::assertNothingSent();
        Log::shouldNotHaveReceived('error');
    }
}
