<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send reminder notification via verified channels.
     * In this implementation email uses Laravel Mail facade,
     * WhatsApp is stubbed to log (integrate provider gateway here).
     */
    public function sendReminder(User $user, string $subject, string $message): void
    {
        if ($user->reminder_email_enabled && $user->email_verified_at) {
            Mail::raw($message, function ($mail) use ($user, $subject) {
                $mail->to($user->email)->subject($subject);
            });
        }

        if ($user->reminder_whatsapp_enabled && $user->whatsapp_verified_at && $user->phone) {
            Log::info('Send WhatsApp notification', [
                'phone' => $user->phone,
                'subject' => $subject,
                'message' => $message,
            ]);
        }
    }

    public function sendVerificationEmail(User $user, string $code): void
    {
        Mail::raw("Kode verifikasi email Anda: {$code}", function ($mail) use ($user) {
            $mail->to($user->email)->subject('Verifikasi Email Kas');
        });
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
}
