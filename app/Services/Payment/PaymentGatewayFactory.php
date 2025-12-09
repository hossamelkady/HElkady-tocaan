<?php

namespace App\Services\Payment;

use App\Services\Payment\Gateways\CreditCardGateway;
use App\Services\Payment\Gateways\PayPalGateway;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    public static function create(string $method): PaymentGatewayInterface
    {
        return match ($method) {
            'credit_card' => new CreditCardGateway(),
            'paypal' => new PayPalGateway(),
            default => throw new InvalidArgumentException("Payment method {$method} is not supported."),
        };
    }
}
