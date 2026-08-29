<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

trait HasUuidPrimaryKey
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    public function uniqueIds(): array
    {
        return ['id'];
    }
}
