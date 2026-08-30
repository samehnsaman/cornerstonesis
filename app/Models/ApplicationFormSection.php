<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationFormSection extends Model
{
    use HasUuidPrimaryKey;

    protected $guarded = [];

    public function fields(): HasMany
    {
        return $this->hasMany(ApplicationFormField::class, 'form_section_id')->orderBy('sort_order');
    }
}
