<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionCycle extends Model
{
    use HasUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['opens_at' => 'datetime', 'closes_at' => 'datetime', 'decision_deadline' => 'date', 'acceptance_deadline' => 'date', 'required_documents' => 'array', 'application_fee' => 'decimal:4'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
