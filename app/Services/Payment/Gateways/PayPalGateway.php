<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Support\Str;

class PayPalGateway implements PaymentGatewayInterface
{
    public function process(float $amount, array $details): array
    {
        // Simulate PayPal processing
        return [
            'status' => 'successful',
            'transaction_id' => 'pp_' . Str::random(10),
            'message' => 'Payment processed successfully via PayPal',
        ];
    }
}
