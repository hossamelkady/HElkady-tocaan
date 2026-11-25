<?php

namespace App\Services\Payment;

use App\Services\Payment\Gateways\CreditCardGateway;
use App\Services\Payment\Gateways\PayPalGateway;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    /**
     * Payment method name => gateway class. Add new gateways here.
     */
    private const GATEWAYS = [
        'credit_card' => CreditCardGateway::class,
        'paypal' => PayPalGateway::class,
    ];

    /**
     * @return list<string>
     */
    public static function supported(): array
    {
        return array_keys(self::GATEWAYS);
    }

    public static function create(string $method): PaymentGatewayInterface
    {
        $class = self::GATEWAYS[$method]
            ?? throw new InvalidArgumentException("Payment method {$method} is not supported.");

        return new $class();
    }
}
