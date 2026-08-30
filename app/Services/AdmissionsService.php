<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionsService
{
    public function __construct(private readonly AuditService $audit) {}

    public function submit(Application $application): Application
    {
        return $this->transition($application, ['draft'], 'submitted', function (Application $locked): array {
            $required = ['address', 'nationality', 'education_level'];
            if (array_diff($required, array_keys(array_filter($locked->form_data ?? []))) !== []) {
                throw ValidationException::withMessages(['application' => __('admissions.incomplete')]);
            }
            if (! $locked->documents()->exists()) {
                throw ValidationException::withMessages(['application' => __('admissions.document_required')]);
            }
            return ['submitted_at' => now()];
        }, 'application.submitted');
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
        return $this->transition($application, ['offered'], $response, fn () => [], 'application.offer_responded');
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
