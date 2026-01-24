<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Ambil Data dari Midtrans
        $payload = $request->all();

        // Log untuk debugging (Cek di storage/logs/laravel.log jika ada masalah)
        Log::info('Midtrans Webhook Received:', $payload);

        // 2. Cari Data Transaksi Berdasarkan Order ID
        $orderId = $payload['order_id'];
        $statusCode = $payload['status_code'];
        $grossAmount = $payload['gross_amount'];

        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            return response()->json(['message' => 'Order ID not found'], 404);
        }

        // 3. Verifikasi Signature Key (Keamanan)
        // Rumus: SHA512(order_id + status_code + gross_amount + ServerKey)
        $serverKey = config('midtrans.server_key'); // Pastikan ini ada di .env dan config
        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($signature !== $payload['signature_key']) {
            return response()->json(['message' => 'Invalid Signature'], 403);
        }

        // 4. Tentukan Status Transaksi
        $transactionStatus = $payload['transaction_status'];
        $type = $payload['payment_type'];
        $fraudStatus = $payload['fraud_status'] ?? null;

        // Default status dari database
        $newStatus = $payment->status;

        if ($transactionStatus == 'capture') {
            // Khusus Kartu Kredit
            if ($fraudStatus == 'challenge') {
                $newStatus = 'pending';
            } else if ($fraudStatus == 'accept') {
                $newStatus = 'approved';
            }
        } else if ($transactionStatus == 'settlement') {
            // Uang sudah masuk (Transfer Bank, Gopay, dll sukses)
            $newStatus = 'approved';
        } else if (
            $transactionStatus == 'cancel' ||
            $transactionStatus == 'deny' ||
            $transactionStatus == 'expire'
        ) {
            // Transaksi gagal / kadaluarsa
            $newStatus = 'rejected';
        } else if ($transactionStatus == 'pending') {
            $newStatus = 'pending';
        }

        // 5. Simpan Perubahan ke Database
        $payment->status = $newStatus;

        // Update Metode Pembayaran jadi lebih spesifik (Misal: dari 'Midtrans' jadi 'gopay' atau 'bank_transfer')
        $payment->method = $type;

        if ($newStatus === 'approved') {
            $payment->approved_at = now();
            $payment->approved_by = null; // Null artinya disetujui oleh Sistem (Bukan Admin manusia)
        }

        $payment->save();

        return response()->json(['message' => 'Payment status updated']);
    }
}
