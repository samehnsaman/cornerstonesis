<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class IntegrationOutbox extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'integration_outbox';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array', 'available_at' => 'datetime', 'processed_at' => 'datetime'];
    }
}
