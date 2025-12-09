<?php

namespace App\Services\Payment;

class PaymentService
{
    public function processPayment(string $method, float $amount, array $details): array
    {
        $gateway = PaymentGatewayFactory::create($method);
        return $gateway->process($amount, $details);
    }
}
