<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationFormVersion extends Model
{
    use HasUuidPrimaryKey;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ApplicationFormSection::class, 'form_version_id')->orderBy('sort_order');
    }
}
