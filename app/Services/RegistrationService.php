<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Registration;
use App\Models\Section;
use App\Models\TermEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly OutboxService $outbox,
    ) {}

    public function register(TermEnrollment $termEnrollment, Section $section, ?string $overrideReason = null): Registration
    {
        return DB::transaction(function () use ($termEnrollment, $section, $overrideReason): Registration {
            $section = Section::query()->lockForUpdate()->findOrFail($section->id);
            $termEnrollment->loadMissing('programEnrollment');
            $personId = $termEnrollment->programEnrollment->person_id;

            if ($section->academic_period_id !== $termEnrollment->academic_period_id) {
                throw ValidationException::withMessages(['section_id' => __('registration.wrong_period')]);
            }

            $blockingHold = DB::table('holds')
                ->where('person_id', $personId)
                ->where('blocks_registration', true)
                ->whereNull('released_at')
                ->where('starts_at', '<=', now())
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->exists();

            if ($blockingHold && ! $overrideReason) {
                throw ValidationException::withMessages(['section_id' => __('registration.blocked_hold')]);
            }

            $activeCount = $section->registrations()->whereIn('status', ['enrolled', 'completed'])->count();
            $status = $activeCount < $section->capacity ? 'enrolled' : 'waitlisted';

            if ($status === 'waitlisted') {
                $waitlisted = $section->registrations()->where('status', 'waitlisted')->count();
                if ($waitlisted >= $section->waitlist_capacity && ! $overrideReason) {
                    throw ValidationException::withMessages(['section_id' => __('registration.section_full')]);
                }
            }

            $this->assertPrerequisites($termEnrollment, $section, $overrideReason);
            $this->assertNoMeetingConflict($termEnrollment, $section, $overrideReason);

            $registration = Registration::create([
                'term_enrollment_id' => $termEnrollment->id,
                'section_id' => $section->id,
                'status' => $status,
                'registered_at' => now(),
                'override_by' => $overrideReason ? auth()->id() : null,
                'override_reason' => $overrideReason,
            ]);

            $this->audit->record('registration.created', $registration, after: $registration->toArray(), reason: $overrideReason);

            if ($status === 'enrolled') {
                $this->outbox->publish('registration.enrolled', 'registration', $registration->id, [
                    'registration_id' => $registration->id,
                    'person_id' => $personId,
                    'section_id' => $section->id,
                ]);
            }

            return $registration;
        }, attempts: 3);
    }

    public function drop(Registration $registration, string $reason): Registration
    {
        return DB::transaction(function () use ($registration, $reason): Registration {
            $registration = Registration::query()->lockForUpdate()->findOrFail($registration->id);
            $before = $registration->toArray();
            $registration->update(['status' => 'dropped', 'dropped_at' => now()]);
            $this->audit->record('registration.dropped', $registration, $before, $registration->fresh()->toArray(), $reason);
            $this->outbox->publish('registration.suspended', 'registration', $registration->id, [
                'registration_id' => $registration->id,
                'section_id' => $registration->section_id,
            ]);

            return $registration->fresh();
        });
    }

    private function assertPrerequisites(TermEnrollment $termEnrollment, Section $section, ?string $overrideReason): void
    {
        $section->loadMissing('courseVersion');
        $required = $section->courseVersion->prerequisite_course_ids ?? [];
        if ($required === [] || $overrideReason) {
            return;
        }

        $completedCourseIds = Grade::query()
            ->join('registrations', 'registrations.id', '=', 'grades.registration_id')
            ->join('term_enrollments', 'term_enrollments.id', '=', 'registrations.term_enrollment_id')
            ->join('program_enrollments', 'program_enrollments.id', '=', 'term_enrollments.program_enrollment_id')
            ->join('sections', 'sections.id', '=', 'registrations.section_id')
            ->join('course_versions', 'course_versions.id', '=', 'sections.course_version_id')
            ->where('program_enrollments.person_id', $termEnrollment->programEnrollment->person_id)
            ->where('grades.status', 'published')
            ->whereNotNull('grades.official_symbol')
            ->pluck('course_versions.course_id')
            ->unique()
            ->all();

        if (array_diff($required, $completedCourseIds) !== []) {
            throw ValidationException::withMessages(['section_id' => __('registration.missing_prerequisites')]);
        }
    }

    private function assertNoMeetingConflict(TermEnrollment $termEnrollment, Section $section, ?string $overrideReason): void
    {
        if ($overrideReason) {
            return;
        }

        $candidateMeetings = $section->meetings()->get();
        if ($candidateMeetings->isEmpty()) {
            return;
        }

        $existingMeetings = DB::table('section_meetings')
            ->join('registrations', 'registrations.section_id', '=', 'section_meetings.section_id')
            ->where('registrations.term_enrollment_id', $termEnrollment->id)
            ->where('registrations.status', 'enrolled')
            ->get();

        foreach ($candidateMeetings as $candidate) {
            $conflict = $existingMeetings->contains(fn ($meeting): bool =>
                (int) $meeting->day_of_week === (int) $candidate->day_of_week
                && $meeting->starts_at < $candidate->ends_at
                && $meeting->ends_at > $candidate->starts_at
            );

            if ($conflict) {
                throw ValidationException::withMessages(['section_id' => __('registration.time_conflict')]);
            }
        }
    }
}
