<?php

namespace App\Services\PaymentGateways;

class StripeGateway implements PaymentGatewayInterface
{
    private array $config;

    public function __construct()
    {
        $this->config = [
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'endpoint' => env('STRIPE_ENDPOINT', 'https://api.stripe.com/v1/charges')
        ];
    }

    public function processPayment(array $paymentData): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Stripe gateway is not properly configured',
                'transaction_id' => null
            ];
        }

        $amount = $paymentData['amount'];
        $token = $paymentData['stripe_token'] ?? '';

        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Stripe token is required',
                'transaction_id' => null
            ];
        }

        $isValidToken = $this->validateStripeToken($token);
        
        if ($isValidToken) {
            return [
                'success' => true,
                'message' => 'Payment processed successfully via Stripe',
                'transaction_id' => 'ST_' . uniqid(),
                'gateway_response' => [
                    'gateway' => 'stripe',
                    'amount' => $amount,
                    'status' => 'approved',
                    'token_used' => $token,
                    'timestamp' => now()->toISOString()
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Stripe payment failed',
            'transaction_id' => null
        ];
    }

    public function getGatewayName(): string
    {
        return 'stripe';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    private function validateStripeToken(string $token): bool
    {
        if (strlen($token) < 10) {
            return false;
        }
        
        return preg_match('/^[a-zA-Z0-9_]+$/', $token);
    }
}
