<?php

namespace App\Services\PaymentGateways;

interface PaymentGatewayInterface
{
    public function processPayment(array $paymentData): array;
    public function getGatewayName(): string;
    public function isConfigured(): bool;
}
