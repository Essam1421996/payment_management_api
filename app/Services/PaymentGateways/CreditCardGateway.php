<?php

namespace App\Services\PaymentGateways;

class CreditCardGateway implements PaymentGatewayInterface
{
    private array $config;

    public function __construct()
    {
        $this->config = [
            'api_key' => env('CREDIT_CARD_API_KEY'),
            'secret' => env('CREDIT_CARD_SECRET'),
            'endpoint' => env('CREDIT_CARD_ENDPOINT', 'https://api.creditcard.com/process')
        ];
    }

    public function processPayment(array $paymentData): array
    {
        $amount = $paymentData['amount'];
        $cardNumber = $paymentData['card_number'] ?? '';
        $cvv = $paymentData['cvv'] ?? '';
        $expiryDate = $paymentData['expiry_date'] ?? '';

        if (empty($cardNumber) || empty($cvv) || empty($expiryDate)) {
            return [
                'success' => false,
                'message' => 'Missing required card information',
                'transaction_id' => null
            ];
        }

        $isValidCard = $this->validateCard($cardNumber, $cvv, $expiryDate);
        
        if ($isValidCard) {
            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'transaction_id' => 'CC_' . uniqid(),
                'gateway_response' => [
                    'gateway' => 'credit_card',
                    'amount' => $amount,
                    'status' => 'approved',
                    'timestamp' => now()->toISOString()
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Payment declined by credit card processor',
            'transaction_id' => null
        ];
    }


    public function getGatewayName(): string
    {
        return 'credit_card';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    private function validateCard(string $cardNumber, string $cvv, string $expiryDate): bool
    {
        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            return false;
        }
        
        if (strlen($cvv) < 3 || strlen($cvv) > 4) {
            return false;
        }
        
        if (!preg_match('/^\d{2}\/\d{2}$/', $expiryDate)) {
            return false;
        }
        
        return $this->luhnCheck($cardNumber);
    }

    private function luhnCheck(string $cardNumber): bool
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        $length = strlen($cardNumber);
        $sum = 0;
        $alternate = false;
        
        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = intval($cardNumber[$i]);
            
            if ($alternate) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit = ($digit % 10) + 1;
                }
            }
            
            $sum += $digit;
            $alternate = !$alternate;
        }
        
        return ($sum % 10) === 0;
    }
}
