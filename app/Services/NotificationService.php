<?php

namespace App\Services;

use App\Mail\ReminderMail;
use App\Mail\VerificationMail;
use App\Models\CompanyContact;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function sendReminder(User $user, string $subject, string $message): void
    {
        if ($user->reminder_email_enabled && $user->email_verified_at) {
            Mail::to($user->email)->send(
                new ReminderMail($subject, $message, $user, $this->companyContact())
            );
        }

        if ($user->reminder_whatsapp_enabled && $user->whatsapp_verified_at && $user->phone) {
            $this->sendWhatsapp($user->phone, $subject, $message);
        }
    }

    public function sendVerificationEmail(User $user, string $code): void
    {
        Mail::to($user->email)->send(
            new VerificationMail($code, $user, $this->companyContact())
        );
    }

    public function sendVerificationWhatsapp(User $user, string $code): void
    {
        if (! $user->phone) {
            return;
        }
        Log::info('Kirim kode verifikasi WhatsApp', [
            'phone' => $user->phone,
            'code' => $code,
        ]);
    }

    private function companyContact(): ?CompanyContact
    {
        return CompanyContact::query()->latest('updated_at')->first();
    }

    private function sendWhatsapp(string $phone, string $subject, string $message): void
    {
        $config = config('services.whatsapp');
        $token = $config['token'] ?? null;
        $phoneNumberId = $config['phone_number_id'] ?? null;

        if (! $token || ! $phoneNumberId) {
            Log::warning('WhatsApp gateway not configured');
            return;
        }

        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $subject . "\n\n" . $message,
            ],
        ];

        /** @var HttpResponse $response */
        $response = Http::withToken($token)
            ->acceptJson()
            ->post("https://graph.facebook.com/v19.0/{$phoneNumberId}/messages", $body);

        if ($response->failed()) {
            Log::error('Failed to send WhatsApp notification', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
