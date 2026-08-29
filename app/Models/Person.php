<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'external_id', 'given_name', 'family_name', 'given_name_ar', 'family_name_ar', 'preferred_name', 'email', 'phone', 'date_of_birth', 'locale', 'status', 'metadata'])]
class Person extends Model
{
    use HasUuidPrimaryKey, SoftDeletes;

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'metadata' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function programEnrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }

    public function displayName(string $locale = 'en'): string
    {
        if ($locale === 'ar' && ($this->given_name_ar || $this->family_name_ar)) {
            return trim("{$this->given_name_ar} {$this->family_name_ar}");
        }

        return trim("{$this->given_name} {$this->family_name}");
    }
}
