<?php

namespace App\Services;

use App\Models\Grade;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GradeService
{
    public function __construct(private readonly AuditService $audit) {}

    public function confirm(Grade $grade, string $symbol): Grade
    {
        if (! in_array($grade->status, ['pending', 'proposed'], true)) {
            throw ValidationException::withMessages(['grade' => __('records.grade_locked')]);
        }

        $before = $grade->toArray();
        $grade->update(['proposed_symbol' => $symbol, 'status' => 'confirmed', 'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);
        $this->audit->record('grade.confirmed', $grade, $before, $grade->fresh()->toArray());

        return $grade->fresh();
    }

    public function publish(Grade $grade): Grade
    {
        if ($grade->status !== 'confirmed' || ! $grade->proposed_symbol) {
            throw ValidationException::withMessages(['grade' => __('records.grade_not_confirmed')]);
        }

        return DB::transaction(function () use ($grade): Grade {
            $before = $grade->toArray();
            $grade->update([
                'official_symbol' => $grade->proposed_symbol,
                'status' => 'published',
                'published_by' => auth()->id(),
                'published_at' => now(),
            ]);
            $this->audit->record('grade.published', $grade, $before, $grade->fresh()->toArray());

            return $grade->fresh();
        });
    }
}
