<?php

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    /**
     * Process a payment.
     *
     * @param float $amount
     * @param array $details
     * @return array Response data (status, transaction_id, etc.)
     */
    public function process(float $amount, array $details): array;
}
