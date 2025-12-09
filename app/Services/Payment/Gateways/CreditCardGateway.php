<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Support\Str;

class CreditCardGateway implements PaymentGatewayInterface
{
    public function process(float $amount, array $details): array
    {
        // Example of retrieving config
        // $apiKey = config('services.stripe.key');

        // Simulate processing
        // In a real scenario, you would use Stripe/Braintree SDKs here.
        return [
            'status' => 'successful',
            'transaction_id' => 'cc_' . Str::random(10),
            'message' => 'Payment processed successfully via Credit Card',
        ];
    }
}
