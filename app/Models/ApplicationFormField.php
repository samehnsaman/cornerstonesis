<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class ApplicationFormField extends Model
{
    use HasUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['required' => 'boolean', 'validation_rules' => 'array', 'options' => 'array', 'visibility_rules' => 'array'];
    }
}
