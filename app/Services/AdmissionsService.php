<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationReview;
use App\Models\LedgerTransaction;
use App\Models\PendingMatriculation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionsService
{
    public function __construct(private readonly AuditService $audit) {}

    public function submit(Application $application): Application
    {
        $submitted = $this->transition($application, ['draft'], 'submitted', function (Application $locked): array {
            $required = ['address', 'nationality', 'education_level'];
            if (array_diff($required, array_keys(array_filter($locked->form_data ?? []))) !== []) {
                throw ValidationException::withMessages(['application' => __('admissions.incomplete')]);
            }
            if (! $locked->documents()->exists()) {
                throw ValidationException::withMessages(['application' => __('admissions.document_required')]);
            }

            return ['submitted_at' => now()];
        }, 'application.submitted');
        $cycle = $submitted->admission_cycle_id ? DB::table('admission_cycles')->where('id', $submitted->admission_cycle_id)->first() : null;
        if ($cycle && (float) $cycle->application_fee > 0 && ! LedgerTransaction::where('reference', 'APPFEE-'.$submitted->id)->exists()) {
            try {
                app(StudentAccountService::class)->post([
                    'person_id' => $submitted->person_id, 'academic_period_id' => $submitted->intake_period_id,
                    'type' => 'application_fee', 'reference' => 'APPFEE-'.$submitted->id, 'currency' => $cycle->currency,
                    'description' => 'Application fee '.$cycle->code, 'effective_on' => today(), 'metadata' => ['application_id' => $submitted->id, 'non_blocking' => true],
                ], [['account_code' => 'student-receivable', 'debit' => $cycle->application_fee, 'credit' => 0], ['account_code' => 'application-fee-revenue', 'debit' => 0, 'credit' => $cycle->application_fee]]);
            } catch (\Throwable $exception) {
                report($exception);
                $this->audit->record('application.fee_assessment_failed', $submitted, metadata: ['non_blocking' => true, 'exception' => $exception::class]);
            }
        }

        return $submitted;
    }

    public function review(Application $application, array $checklist, string $recommendation, ?string $notes): ApplicationReview
    {
        return DB::transaction(function () use ($application, $checklist, $recommendation, $notes): ApplicationReview {
            $locked = Application::query()->lockForUpdate()->findOrFail($application->id);
            if (! in_array($locked->status, ['submitted', 'under_review'], true)) {
                throw ValidationException::withMessages(['application' => __('admissions.not_reviewable')]);
            }
            $before = $locked->toArray();
            $locked->update(['status' => 'under_review']);
            $review = $locked->reviews()->create(['reviewer_id' => auth()->id(), 'recommendation' => $recommendation, 'notes' => $notes, 'checklist' => $checklist, 'completed_at' => now()]);
            $this->audit->record('application.reviewed', $locked, $before, $locked->fresh()->toArray(), metadata: ['review_id' => $review->id, 'recommendation' => $recommendation]);

            return $review;
        });
    }

    public function decide(Application $application, string $decision, string $reason, array $conditions = []): Application
    {
        if (! in_array($decision, ['offered', 'denied', 'waitlisted'], true)) {
            throw ValidationException::withMessages(['decision' => __('admissions.invalid_decision')]);
        }

        return $this->transition($application, ['submitted', 'under_review', 'waitlisted'], $decision, fn () => ['decided_at' => now(), 'decided_by' => auth()->id(), 'decision_reason' => $reason, 'conditions' => $conditions], 'application.decided', $reason);
    }

    public function respondToOffer(Application $application, string $response): Application
    {
        if (! in_array($response, ['accepted', 'declined'], true)) {
            throw ValidationException::withMessages(['response' => __('admissions.invalid_response')]);
        }
        $updated = $this->transition($application, ['offered'], $response, fn () => [], 'application.offer_responded');
        if ($response === 'accepted') {
            PendingMatriculation::firstOrCreate(['application_id' => $updated->id], [
                'intake_period_id' => $updated->intake_period_id,
                'campus_id' => $updated->admission_cycle_id ? DB::table('admission_cycles')->where('id', $updated->admission_cycle_id)->value('campus_id') : null,
                'status' => 'pending',
            ]);
        }

        return $updated;
    }

    private function transition(Application $application, array $from, string $to, callable $extra, string $auditAction, ?string $reason = null): Application
    {
        return DB::transaction(function () use ($application, $from, $to, $extra, $auditAction, $reason): Application {
            $locked = Application::query()->lockForUpdate()->findOrFail($application->id);
            if (! in_array($locked->status, $from, true)) {
                throw ValidationException::withMessages(['application' => __('admissions.invalid_transition')]);
            }
            $before = $locked->toArray();
            $locked->update(['status' => $to, ...$extra($locked)]);
            $this->audit->record($auditAction, $locked, $before, $locked->fresh()->toArray(), $reason);

            return $locked->fresh();
        });
    }
}
