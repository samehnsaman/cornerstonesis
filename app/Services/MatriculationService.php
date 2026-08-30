<?php

namespace App\Services;

use App\Models\PendingMatriculation;
use App\Models\ProgramEnrollment;
use App\Models\TermEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatriculationService
{
    public function __construct(private readonly AuditService $audit) {}

    public function approve(PendingMatriculation $pending, array $data): ProgramEnrollment
    {
        return DB::transaction(function () use ($pending, $data): ProgramEnrollment {
            $locked = PendingMatriculation::query()->lockForUpdate()->findOrFail($pending->id);
            if ($locked->status === 'approved' && $locked->program_enrollment_id) {
                return ProgramEnrollment::findOrFail($locked->program_enrollment_id);
            }
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['matriculation' => __('admin.invalid_matriculation')]);
            }

            $application = $locked->application()->with('person')->firstOrFail();
            abort_unless($application->status === 'accepted', 409);
            $year = (int) now()->year;
            DB::table('student_number_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $sequence = DB::table('student_number_sequences')->where('year', $year)->lockForUpdate()->first();
            $next = ((int) $sequence->last_number) + 1;
            DB::table('student_number_sequences')->where('year', $year)->update(['last_number' => $next, 'updated_at' => now()]);

            $enrollment = ProgramEnrollment::create([
                'person_id' => $application->person_id,
                'program_id' => $application->program_id,
                'curriculum_version_id' => $data['curriculum_version_id'],
                'campus_id' => $data['campus_id'],
                'admit_application_id' => $application->id,
                'student_number' => sprintf('%d-%06d', $year, $next),
                'started_on' => $data['started_on'],
                'status' => 'active',
            ]);

            if ($data['create_term_enrollment']) {
                TermEnrollment::create(['program_enrollment_id' => $enrollment->id, 'academic_period_id' => $data['intake_period_id'], 'status' => 'eligible', 'credit_limit' => $data['credit_limit'] ?? 18]);
            }

            $locked->update([
                'curriculum_version_id' => $data['curriculum_version_id'], 'campus_id' => $data['campus_id'],
                'intake_period_id' => $data['intake_period_id'], 'status' => 'approved', 'approved_by' => auth()->id(),
                'approved_at' => now(), 'program_enrollment_id' => $enrollment->id,
                'create_term_enrollment' => $data['create_term_enrollment'], 'override_reason' => $data['override_reason'] ?? null,
            ]);
            $this->audit->record('matriculation.approved', $locked, after: $locked->fresh()->toArray(), reason: $data['override_reason'] ?? null, metadata: ['student_number' => $enrollment->student_number]);

            return $enrollment;
        }, 3);
    }
}
