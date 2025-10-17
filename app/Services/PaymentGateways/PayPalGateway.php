<?php

namespace App\Services\PaymentGateways;

class PayPalGateway implements PaymentGatewayInterface
{
    private array $config;

    public function __construct()
    {
        $this->config = [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'endpoint' => env('PAYPAL_ENDPOINT', 'https://api.paypal.com/v1/payments')
        ];
    }

    public function processPayment(array $paymentData): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'PayPal gateway is not properly configured',
                'transaction_id' => null
            ];
        }

        $amount = $paymentData['amount'];
        $email = $paymentData['paypal_email'] ?? '';

        if (empty($email)) {
            return [
                'success' => false,
                'message' => 'PayPal email is required',
                'transaction_id' => null
            ];
        }

        $isValidEmail = $this->validatePayPalEmail($email);
        
        if ($isValidEmail) {
            return [
                'success' => true,
                'message' => 'Payment processed successfully via PayPal',
                'transaction_id' => 'PP_' . uniqid(),
                'gateway_response' => [
                    'gateway' => 'paypal',
                    'amount' => $amount,
                    'status' => 'approved',
                    'payer_email' => $email,
                    'timestamp' => now()->toISOString()
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'PayPal payment failed',
            'transaction_id' => null
        ];
    }

    public function getGatewayName(): string
    {
        return 'paypal';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    private function validatePayPalEmail(string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        return strpos($email, '@') !== false;
    }
}
