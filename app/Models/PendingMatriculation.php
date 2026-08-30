<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingMatriculation extends Model
{
    use HasUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'create_term_enrollment' => 'boolean'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
