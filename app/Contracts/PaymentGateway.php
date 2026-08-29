<?php

namespace App\Contracts;

interface PaymentGateway
{
    public function checkout(array $payment): array;
    public function verifyWebhook(string $body, string $signature): array;
}
