<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasUuidPrimaryKey;

    protected $guarded = [];

    public function courseVersion(): BelongsTo
    {
        return $this->belongsTo(CourseVersion::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function campus(): BelongsTo { return $this->belongsTo(Campus::class); }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(SectionMeeting::class);
    }
}
