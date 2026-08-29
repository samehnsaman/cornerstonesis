<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasUuidPrimaryKey;

    protected $guarded = [];

    public function versions(): HasMany
    {
        return $this->hasMany(CourseVersion::class);
    }
}
