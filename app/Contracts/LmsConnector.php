<?php

namespace App\Contracts;

interface LmsConnector
{
    public function discover(): array;
    public function handle(string $event, array $payload): array;
    public function loginUrl(string $username): string;
}
