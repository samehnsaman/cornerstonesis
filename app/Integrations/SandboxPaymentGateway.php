<?php

namespace App\Integrations;

use App\Contracts\PaymentGateway;
use Illuminate\Support\Str;

class SandboxPaymentGateway implements PaymentGateway
{
    public function checkout(array $payment): array
    {
        $reference = 'SISPOC-PAY-'.Str::upper(Str::random(12));

        return ['reference' => $reference, 'url' => route('payments.sandbox', $reference)];
    }

    public function verifyWebhook(string $body, string $signature): array
    {
        abort_unless(hash_equals(hash_hmac('sha256', $body, (string) config('integrations.payments.webhook_secret')), $signature), 401);

        return json_decode($body, true, flags: JSON_THROW_ON_ERROR);
    }
}
