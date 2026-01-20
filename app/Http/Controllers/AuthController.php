<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'profile_photo_url' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
            ],
        ]);
    }

    public function register(Request $request, NotificationService $notifier): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'role' => ['prohibited'], // prevent role escalation via register
        ]);

        $emailCode = Str::upper(Str::random(6));
        $waCode = Str::upper(Str::random(6));

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'phone' => $validated['phone'],
            'email_verification_code' => $emailCode,
            'whatsapp_verification_code' => $waCode,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        // send verification
        $notifier->sendVerificationEmail($user, $emailCode);
        $notifier->sendVerificationWhatsapp($user, $waCode);

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'profile_photo_url' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
            ],
            'verification' => [
                'email_sent' => true,
                'whatsapp_sent' => true,
            ],
        ], 201);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'profile_photo_url' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
                'auto_reminder_enabled' => (bool) $user->auto_reminder_enabled,
                'reminder_email_enabled' => (bool) $user->reminder_email_enabled,
                'reminder_whatsapp_enabled' => (bool) $user->reminder_whatsapp_enabled,
                'email_verified' => (bool) $user->email_verified_at,
                'whatsapp_verified' => (bool) $user->whatsapp_verified_at,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'phone' => ['sometimes', 'string', 'max:32', 'unique:users,phone,' . $user->id],
            'reminder_email_enabled' => ['sometimes', 'boolean'],
            'reminder_whatsapp_enabled' => ['sometimes', 'boolean'],
            'auto_reminder_enabled' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('phone', $validated)) {
            if ($validated['phone'] !== $user->phone) {
                $user->phone = $validated['phone'];
                $user->whatsapp_verified_at = null;
                $user->whatsapp_verification_code = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(6));
            }
        }

        foreach (['reminder_email_enabled', 'reminder_whatsapp_enabled', 'auto_reminder_enabled'] as $field) {
            if (array_key_exists($field, $validated)) {
                $user->$field = (bool) $validated[$field];
            }
        }

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = $file->store('profile_photos', 'public');

            // Optionally delete previous photo
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $user->profile_photo = $path;
            $user->save();
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'profile_photo_url' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
                'auto_reminder_enabled' => (bool) $user->auto_reminder_enabled,
                'reminder_email_enabled' => (bool) $user->reminder_email_enabled,
                'reminder_whatsapp_enabled' => (bool) $user->reminder_whatsapp_enabled,
                'email_verified' => (bool) $user->email_verified_at,
                'whatsapp_verified' => (bool) $user->whatsapp_verified_at,
            ],
        ]);
    }

    public function updateReminderChannels(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'reminder_email_enabled' => ['required', 'boolean'],
            'reminder_whatsapp_enabled' => ['required', 'boolean'],
        ]);

        if ($validated['reminder_email_enabled'] && ! $user->email_verified_at) {
            return response()->json(['message' => 'Verifikasi email dulu di profil'], 422);
        }

        if ($validated['reminder_whatsapp_enabled']) {
            if (! $user->phone) {
                return response()->json(['message' => 'Isi nomor WhatsApp di profil lebih dulu'], 422);
            }
            if (! $user->whatsapp_verified_at) {
                return response()->json(['message' => 'Verifikasi WhatsApp dulu di profil'], 422);
            }
        }

        $user->reminder_email_enabled = (bool) $validated['reminder_email_enabled'];
        $user->reminder_whatsapp_enabled = (bool) $validated['reminder_whatsapp_enabled'];
        $user->auto_reminder_enabled = $user->reminder_email_enabled || $user->reminder_whatsapp_enabled;
        $user->save();

        return response()->json([
            'message' => 'Preferensi pengingat diperbarui',
            'auto_reminder_enabled' => $user->auto_reminder_enabled,
            'reminder_email_enabled' => $user->reminder_email_enabled,
            'reminder_whatsapp_enabled' => $user->reminder_whatsapp_enabled,
            'email_verified' => (bool) $user->email_verified_at,
            'whatsapp_verified' => (bool) $user->whatsapp_verified_at,
        ]);
    }

    public function toggleReminder(Request $request, NotificationService $notifier): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $user->auto_reminder_enabled = (bool) $validated['enabled'];
        $user->save();

        if ($user->auto_reminder_enabled) {
            $notifier->sendReminder($user, 'Pengingat kas diaktifkan', 'Pengingat kas otomatis telah diaktifkan. Anda akan menerima notifikasi sebelum jatuh tempo.');
        }

        return response()->json([
            'message' => 'Status pengingat diperbarui',
            'auto_reminder_enabled' => $user->auto_reminder_enabled,
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        if ($user->email_verification_code !== $validated['code']) {
            return response()->json(['message' => 'Kode verifikasi email salah'], 422);
        }

        $user->email_verified_at = now();
        $user->email_verification_code = null;
        $user->save();

        return response()->json(['message' => 'Email terverifikasi']);
    }

    public function verifyWhatsapp(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        if ($user->whatsapp_verification_code !== $validated['code']) {
            return response()->json(['message' => 'Kode verifikasi WA salah'], 422);
        }

        $user->whatsapp_verified_at = now();
        $user->whatsapp_verification_code = null;
        $user->save();

        return response()->json(['message' => 'WhatsApp terverifikasi']);
    }
}
