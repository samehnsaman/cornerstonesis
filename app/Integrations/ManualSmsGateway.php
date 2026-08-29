<?php

namespace App\Integrations;

use App\Contracts\SmsGateway;
use Illuminate\Support\Str;

class ManualSmsGateway implements SmsGateway
{
    public function send(string $recipient, string $message): string
    {
        logger()->info('POC SMS recorded', ['recipient_suffix' => Str::substr($recipient, -4), 'message_length' => Str::length($message)]);

        return 'manual-'.Str::uuid();
    }
}
