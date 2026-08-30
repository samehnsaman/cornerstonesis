<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_poc' => 'boolean', 'supported_currencies' => 'array', 'transcript_branding' => 'array'];
    }
}
