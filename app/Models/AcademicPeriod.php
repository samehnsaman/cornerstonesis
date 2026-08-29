<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class AcademicPeriod extends Model
{
    use HasUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date', 'ends_on' => 'date',
            'registration_opens_at' => 'datetime', 'registration_closes_at' => 'datetime',
            'add_drop_deadline' => 'date', 'withdrawal_deadline' => 'date',
        ];
    }
}
