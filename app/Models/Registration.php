<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Registration extends Model
{
    use HasUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['registered_at' => 'datetime', 'dropped_at' => 'datetime', 'withdrawn_at' => 'datetime'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
    public function termEnrollment(): BelongsTo
    {
        return $this->belongsTo(TermEnrollment::class);
    }

    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class);
    }
}
