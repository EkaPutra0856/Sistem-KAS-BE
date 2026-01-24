<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function createPayment(array $data)
    {
        return Payment::create($data);
    }
}
