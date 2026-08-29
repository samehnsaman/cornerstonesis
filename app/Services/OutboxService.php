<?php

namespace App\Services;

use App\Jobs\ProcessIntegrationOutbox;
use App\Models\IntegrationOutbox;
use Illuminate\Support\Str;

class OutboxService
{
    public function publish(string $eventType, string $aggregateType, string $aggregateId, array $payload, string $connector = 'moodle'): IntegrationOutbox
    {
        $key = hash('sha256', implode('|', [$connector, $eventType, $aggregateType, $aggregateId, json_encode($payload)]));

        $item = IntegrationOutbox::firstOrCreate(
            ['idempotency_key' => $key],
            [
                'id' => (string) Str::uuid(),
                'connector' => $connector,
                'event_type' => $eventType,
                'aggregate_type' => $aggregateType,
                'aggregate_id' => $aggregateId,
                'payload' => $payload,
                'status' => 'pending',
                'available_at' => now(),
            ],
        );

        if ($item->wasRecentlyCreated && config('integrations.moodle.enabled')) {
            ProcessIntegrationOutbox::dispatch($item->id)->afterCommit();
        }

        return $item;
    }
}
