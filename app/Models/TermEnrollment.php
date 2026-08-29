<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TermEnrollment extends Model
{
    use HasUuidPrimaryKey;

    protected $guarded = [];

    public function programEnrollment(): BelongsTo
    {
        return $this->belongsTo(ProgramEnrollment::class);
    }
    public function academicPeriod(): BelongsTo { return $this->belongsTo(AcademicPeriod::class); }
    public function registrations(): HasMany { return $this->hasMany(Registration::class); }
}
