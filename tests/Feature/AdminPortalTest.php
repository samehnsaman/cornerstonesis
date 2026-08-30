<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\Application;
use App\Models\Campus;
use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\PendingMatriculation;
use App\Models\Person;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_templates_can_be_provisioned_without_demo_accounts(): void
    {
        $this->seed(RoleTemplateSeeder::class);

        $this->assertDatabaseCount('roles', 7);
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
        $this->assertSame(['*'], Role::where('code', 'system-administrator')->firstOrFail()->permissions);
    }

    public function test_role_assignment_effective_dates_are_enforced(): void
    {
        $this->seed();
        $user = User::factory()->create(['status' => 'active']);
        $role = Role::where('code', 'read-only-auditor')->firstOrFail();

        DB::table('role_assignments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'role_id' => $role->id,
            'starts_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse($user->hasPermission('admin.access'));

        DB::table('role_assignments')->where('user_id', $user->id)->update([
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $this->assertTrue($user->fresh()->hasPermission('admin.access'));

        DB::table('role_assignments')->where('user_id', $user->id)->update(['ends_at' => now()->subMinute()]);

        $this->assertFalse($user->fresh()->hasPermission('admin.access'));
    }

    public function test_admin_portal_requires_permission_and_staff_mfa(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $applicant = User::where('email', 'applicant@example.test')->firstOrFail();

        $this->actingAs($applicant)->get('/admin')->assertForbidden();
        $this->actingAs($admin)->get('/admin')->assertRedirect('/mfa');
        $this->withSession(['mfa_user_id' => $admin->id])->get('/admin')->assertOk();
    }

    public function test_department_scope_prevents_cross_department_program_creation(): void
    {
        $this->seed();
        $organizationId = DB::table('organizations')->value('id');
        $otherCampus = Campus::create(['organization_id' => $organizationId, 'code' => 'OTHER', 'name_en' => 'Other Campus', 'timezone' => 'UTC']);
        $otherDepartment = Department::create(['organization_id' => $organizationId, 'campus_id' => $otherCampus->id, 'code' => 'BUS', 'name_en' => 'Business']);
        $homeDepartment = Department::where('code', 'CSCI')->firstOrFail();
        $user = User::factory()->create(['status' => 'active']);
        $role = Role::where('code', 'department-administrator')->firstOrFail();
        DB::table('role_assignments')->insert(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role_id' => $role->id, 'department_id' => $homeDepartment->id, 'campus_id' => $homeDepartment->campus_id, 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($user)->withSession(['mfa_user_id' => $user->id])->post('/admin/programs', [
            'department_id' => $otherDepartment->id, 'code' => 'BBA', 'name_en' => 'Business Administration',
            'award_type' => 'bachelor', 'required_credits' => 120, 'duration_terms' => 8,
        ])->assertForbidden();
        $this->assertDatabaseMissing('programs', ['code' => 'BBA']);
    }

    public function test_publishing_a_catalog_version_is_audited(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $curriculum = CurriculumVersion::firstOrFail();
        $curriculum->update(['status' => 'draft']);

        $this->actingAs($admin)->withSession(['mfa_user_id' => $admin->id])->post("/admin/publish/curriculum/{$curriculum->id}", ['reason' => 'Approved catalog release'])->assertRedirect();

        $this->assertSame('published', $curriculum->fresh()->status);
        $this->assertDatabaseHas('audit_events', ['action' => 'curriculum.published', 'subject_id' => $curriculum->id, 'reason' => 'Approved catalog release']);
    }

    public function test_matriculation_is_idempotent_and_can_create_the_first_term(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $program = Program::firstOrFail();
        $curriculum = CurriculumVersion::firstOrFail();
        $campus = Campus::firstOrFail();
        $period = AcademicPeriod::firstOrFail();
        $applicant = User::factory()->create(['status' => 'active']);
        $person = Person::create(['user_id' => $applicant->id, 'external_id' => 'APP-MATRICULATION', 'given_name' => 'Ready', 'family_name' => 'Student', 'email' => $applicant->email]);
        $application = Application::create(['person_id' => $person->id, 'program_id' => $program->id, 'intake_period_id' => $period->id, 'application_number' => 'A2026MATRIC', 'status' => 'accepted', 'form_snapshot' => ['type' => 'legacy']]);
        $pending = PendingMatriculation::create(['application_id' => $application->id, 'status' => 'pending', 'campus_id' => $campus->id, 'intake_period_id' => $period->id]);
        $payload = ['curriculum_version_id' => $curriculum->id, 'campus_id' => $campus->id, 'intake_period_id' => $period->id, 'started_on' => '2026-09-01', 'create_term_enrollment' => true, 'credit_limit' => 18];

        $this->actingAs($admin)->withSession(['mfa_user_id' => $admin->id])->post("/admin/matriculation/{$pending->id}/approve", $payload)->assertRedirect();
        $this->post("/admin/matriculation/{$pending->id}/approve", $payload)->assertRedirect();

        $this->assertDatabaseCount('student_number_sequences', 1);
        $this->assertSame(1, DB::table('program_enrollments')->where('admit_application_id', $application->id)->count());
        $enrollmentId = DB::table('program_enrollments')->where('admit_application_id', $application->id)->value('id');
        $this->assertSame(1, DB::table('term_enrollments')->where('program_enrollment_id', $enrollmentId)->count());
        $this->assertDatabaseHas('audit_events', ['action' => 'matriculation.approved', 'subject_id' => $pending->id]);
    }
}
