<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['person_id', 'program_id', 'intake_period_id', 'application_number', 'status', 'submitted_at', 'decided_at', 'decided_by', 'decision_reason', 'conditions', 'form_data'])]
class Application extends Model
{
    use HasUuidPrimaryKey;

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'conditions' => 'array',
            'form_data' => 'array',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function intakePeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class, 'intake_period_id');
    }

    public function documents(): HasMany { return $this->hasMany(ApplicationDocument::class); }
    public function reviews(): HasMany { return $this->hasMany(ApplicationReview::class); }
}
