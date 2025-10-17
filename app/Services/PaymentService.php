<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentGateways\PaymentGatewayInterface;
use App\Services\PaymentGateways\CreditCardGateway;
use App\Services\PaymentGateways\PayPalGateway;
use App\Services\PaymentGateways\StripeGateway;
use Illuminate\Support\Str;

class PaymentService
{
    private array $gateways;

    public function __construct()
    {
        $this->gateways = [
            'credit_card' => new CreditCardGateway(),
            'paypal' => new PayPalGateway(),
            'stripe' => new StripeGateway(),
        ];
    }

    public function processPayment(Order $order, string $paymentMethod, array $paymentData): array
    {
        if (!$order->canProcessPayment()) {
            return [
                'success' => false,
                'message' => 'Order must be in confirmed status to process payment',
                'payment' => null
            ];
        }

        $gateway = $this->getGateway($paymentMethod);
        if (!$gateway) {
            return [
                'success' => false,
                'message' => 'Unsupported payment method',
                'payment' => null
            ];
        }

        $paymentData['amount'] = $order->total_amount;
        $result = $gateway->processPayment($paymentData);

        $payment = Payment::create([
            'payment_id' => $result['transaction_id'] ?? Str::uuid(),
            'order_id' => $order->id,
            'status' => $result['success'] ? 'successful' : 'failed',
            'payment_method' => $paymentMethod,
            'gateway_name' => $gateway->getGatewayName(),
            'amount' => $order->total_amount,
            'gateway_response' => $result['gateway_response'] ?? null
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['message'],
            'payment' => $payment
        ];
    }

    public function getAvailableGateways(): array
    {
        $available = [];
        foreach ($this->gateways as $name => $gateway) {
            if ($gateway->isConfigured()) {
                $available[] = [
                    'name' => $name,
                    'display_name' => ucfirst(str_replace('_', ' ', $name)),
                    'gateway_name' => $gateway->getGatewayName()
                ];
            }
        }
        return $available;
    }

    private function getGateway(string $paymentMethod): ?PaymentGatewayInterface
    {
        return $this->gateways[$paymentMethod] ?? null;
    }
}
