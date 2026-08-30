<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\Application;
use App\Models\Person;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdmissionsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicant_completes_and_submits_a_documented_application(): void
    {
        Storage::fake('local');
        $this->seed();
        $user = User::first();
        $this->actingAs($user)->post('/applications', [
            'program_id' => Program::first()->id,
            'intake_period_id' => AcademicPeriod::first()->id,
        ])->assertRedirect();

        $application = Application::first();
        $this->assertSame('draft', $application->status);

        $this->patch("/applications/{$application->id}", [
            'address' => '100 Demo Street', 'nationality' => 'Example',
            'education_level' => 'Secondary school', 'statement' => 'Synthetic demonstration statement.',
        ])->assertRedirect();
        $this->post("/applications/{$application->id}/documents", [
            'type' => 'transcript', 'document' => UploadedFile::fake()->create('transcript.pdf', 100, 'application/pdf'),
        ])->assertRedirect();
        $this->post("/applications/{$application->id}/submit")->assertRedirect();

        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertDatabaseCount('application_documents', 1);
        $this->assertDatabaseHas('audit_events', ['action' => 'application.submitted', 'subject_id' => $application->id]);
    }

    public function test_staff_review_decision_and_applicant_acceptance_are_audited(): void
    {
        $this->seed();
        $staff = User::where('email', 'admissions@example.test')->firstOrFail();
        $applicant = User::factory()->create(['email' => 'second@example.test']);
        $person = Person::create(['user_id' => $applicant->id, 'external_id' => 'APP-SECOND', 'given_name' => 'Second', 'family_name' => 'Applicant', 'email' => $applicant->email]);
        $application = Application::create(['person_id' => $person->id, 'program_id' => Program::first()->id, 'intake_period_id' => AcademicPeriod::first()->id, 'application_number' => 'A2026SECOND', 'status' => 'submitted', 'submitted_at' => now(), 'form_data' => ['address' => 'Demo', 'nationality' => 'Example', 'education_level' => 'Secondary']]);

        $this->actingAs($staff)->withSession(['mfa_user_id' => $staff->id])->post("/applications/{$application->id}/reviews", [
            'recommendation' => 'offer', 'notes' => 'All synthetic checks passed.',
            'checklist' => ['identity' => true, 'academic_records' => true, 'eligibility' => true],
        ])->assertRedirect();
        $this->post("/applications/{$application->id}/decision", ['decision' => 'offered', 'reason' => 'Meets demo admission criteria.', 'conditions' => []])->assertRedirect();
        $this->actingAs($applicant)->post("/applications/{$application->id}/response", ['response' => 'accepted'])->assertRedirect();

        $this->assertSame('accepted', $application->fresh()->status);
        $this->assertDatabaseHas('application_reviews', ['application_id' => $application->id, 'recommendation' => 'offer']);
        $this->assertDatabaseHas('audit_events', ['action' => 'application.offer_responded', 'subject_id' => $application->id]);
    }

    public function test_unprivileged_user_cannot_review_an_application(): void
    {
        $this->seed();
        $application = Application::create(['person_id' => Person::first()->id, 'program_id' => Program::first()->id, 'intake_period_id' => AcademicPeriod::first()->id, 'application_number' => 'A2026DENIED', 'status' => 'submitted']);
        $outsider = User::factory()->create();
        $this->actingAs($outsider)->post("/applications/{$application->id}/reviews", [
            'recommendation' => 'offer', 'checklist' => ['identity' => true, 'academic_records' => true, 'eligibility' => true],
        ])->assertForbidden();
    }
}
