<?php

namespace App\Services;

use App\Repositories\PaymentRepositoryInterface;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;

class PayService
{
    protected $paymentRepository;

    public function __construct(PaymentRepositoryInterface $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;

        // Konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Create a Payment record and return Midtrans Snap token
     *
     * Expected $data keys: user_id, recorded_by, schedule_id, week_label,
     * due_date, amount, customer_name, customer_email, customer_phone, items (optional)
     *
     * @param array $data
     * @return array
     */
    public function processPayment($data)
    {
        $payment = $this->paymentRepository->createPayment([
            'user_id' => $data['user_id'] ?? null,
            'recorded_by' => $data['recorded_by'] ?? null,
            'schedule_id' => $data['schedule_id'] ?? null,
            'week_label' => $data['week_label'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'amount' => $data['amount'] ?? 0,
            'method' => 'transfer',
            'status' => 'pending',
        ]);

        $orderId = 'KAS-' . $payment->id . '-' . Str::upper(Str::random(6));

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $payment->amount,
            ],
            'customer_details' => [
                'first_name' => $data['customer_name'] ?? '',
                'email' => $data['customer_email'] ?? '',
                'phone' => $data['customer_phone'] ?? '',
            ],
        ];

        $snapToken = Snap::getSnapToken($params);

        $payment->update([
            'order_id' => $orderId,
            'snap_token' => $snapToken,
        ]);

        return [
            'payment' => $payment,
            'snap_token' => $snapToken,
        ];
    }
}
