<?php

namespace App\Jobs;

use App\Contracts\LmsConnector;
use App\Models\IntegrationOutbox;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessIntegrationOutbox implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public array $backoff = [30, 120, 600, 1800];

    public function __construct(public string $outboxId) {}

    public function handle(LmsConnector $connector): void
    {
        $item = IntegrationOutbox::query()->findOrFail($this->outboxId);
        if ($item->status === 'completed') {
            return;
        }

        $item->increment('attempts');
        try {
            $connector->handle($item->event_type, $item->payload);
            $item->update(['status' => 'completed', 'processed_at' => now(), 'last_error' => null]);
        } catch (Throwable $e) {
            $item->update(['status' => $this->attempts() >= $this->tries ? 'dead_letter' : 'retrying', 'last_error' => str($e->getMessage())->limit(2000)]);
            throw $e;
        }
    }
}
