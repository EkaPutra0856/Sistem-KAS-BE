<?php

namespace App\Repositories;

interface PaymentRepositoryInterface
{
    public function createPayment(array $data);
}
