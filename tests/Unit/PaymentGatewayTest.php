<?php

namespace Tests\Unit;

use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\Gateways\CreditCardGateway;
use App\Services\Payment\Gateways\PayPalGateway;
use App\Services\Payment\PaymentGatewayInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PaymentGatewayTest extends TestCase
{
    public function test_factory_creates_credit_card_gateway()
    {
        $gateway = PaymentGatewayFactory::create('credit_card');
        $this->assertInstanceOf(CreditCardGateway::class, $gateway);
        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
    }

    public function test_factory_creates_paypal_gateway()
    {
        $gateway = PaymentGatewayFactory::create('paypal');
        $this->assertInstanceOf(PayPalGateway::class, $gateway);
    }

    public function test_factory_throws_exception_for_invalid_method()
    {
        $this->expectException(InvalidArgumentException::class);
        PaymentGatewayFactory::create('unsupported_method');
    }

    public function test_credit_card_gateway_process_returns_success_structure()
    {
        $gateway = new CreditCardGateway();
        $result = $gateway->process(100.00, ['card_number' => '1234']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertEquals('successful', $result['status']);
    }

    public function test_paypal_gateway_process_returns_success_structure()
    {
        $result = (new PayPalGateway())->process(25.50, []);

        $this->assertEquals('successful', $result['status']);
        $this->assertStringStartsWith('pp_', $result['transaction_id']);
    }

    public function test_factory_lists_supported_methods()
    {
        $this->assertSame(['credit_card', 'paypal'], PaymentGatewayFactory::supported());
    }
}
