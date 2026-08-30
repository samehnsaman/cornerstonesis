<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 10)->default('en');
            $table->boolean('mfa_required')->default(false);
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('default_locale', 10)->default('en');
            $table->boolean('is_poc')->default(true);
            $table->timestamps();
        });

        Schema::create('campuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->string('timezone')->default('UTC');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('people', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('external_id')->unique();
            $table->string('given_name');
            $table->string('family_name');
            $table->string('given_name_ar')->nullable();
            $table->string('family_name_ar')->nullable();
            $table->string('preferred_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 32)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('locale', 10)->default('en');
            $table->string('status')->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->json('permissions');
            $table->boolean('privileged')->default(false);
            $table->timestamps();
        });

        Schema::create('role_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('campus_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('program_id')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'role_id']);
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('department_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->string('award_type');
            $table->decimal('required_credits', 8, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('role_assignments', function (Blueprint $table) {
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
        });

        Schema::create('curriculum_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_id')->constrained()->cascadeOnDelete();
            $table->string('version');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->decimal('minimum_gpa', 5, 3)->default(2.000);
            $table->json('completion_rules')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->unique(['program_id', 'version']);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('department_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('title_en');
            $table->string('title_ar')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('course_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained()->cascadeOnDelete();
            $table->string('version');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->decimal('credit_hours', 6, 2);
            $table->decimal('lecture_hours', 6, 2)->default(0);
            $table->decimal('lab_hours', 6, 2)->default(0);
            $table->json('prerequisite_course_ids')->nullable();
            $table->json('corequisite_course_ids')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'version']);
        });

        Schema::create('curriculum_courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('curriculum_version_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('course_version_id')->constrained()->restrictOnDelete();
            $table->string('requirement_type')->default('required');
            $table->unsignedSmallInteger('recommended_sequence')->nullable();
            $table->timestamps();
            $table->unique(['curriculum_version_id', 'course_version_id']);
        });

        Schema::create('academic_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->string('type');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->dateTime('registration_opens_at')->nullable();
            $table->dateTime('registration_closes_at')->nullable();
            $table->date('add_drop_deadline')->nullable();
            $table->date('withdrawal_deadline')->nullable();
            $table->string('status')->default('planned');
            $table->timestamps();
        });

        Schema::table('academic_periods', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('academic_periods')->nullOnDelete();
        });

        Schema::create('grading_scales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('grade_scale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('grading_scale_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 12);
            $table->decimal('minimum_percent', 6, 2);
            $table->decimal('grade_points', 5, 3)->nullable();
            $table->boolean('earns_credit')->default(true);
            $table->boolean('included_in_gpa')->default(true);
            $table->timestamps();
            $table->unique(['grading_scale_id', 'symbol']);
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_version_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('academic_period_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('campus_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('grading_scale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('instructor_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('code');
            $table->unsignedInteger('capacity')->default(30);
            $table->unsignedInteger('waitlist_capacity')->default(0);
            $table->string('delivery_mode')->default('in_person');
            $table->string('status')->default('planned');
            $table->string('moodle_idnumber')->nullable()->unique();
            $table->string('moodle_course_id')->nullable();
            $table->timestamps();
            $table->unique(['academic_period_id', 'code']);
        });

        Schema::create('section_meetings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('section_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('room')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('program_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('intake_period_id')->constrained('academic_periods')->restrictOnDelete();
            $table->string('application_number')->unique();
            $table->string('status')->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_reason')->nullable();
            $table->json('conditions')->nullable();
            $table->json('form_data')->nullable();
            $table->timestamps();
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256', 64);
            $table->string('verification_status')->default('pending');
            $table->timestamps();
        });

        Schema::create('application_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('recommendation');
            $table->text('notes')->nullable();
            $table->json('checklist')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('program_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('program_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('curriculum_version_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('campus_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('admit_application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->string('student_number')->unique();
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('term_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('academic_period_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('eligible');
            $table->decimal('credit_limit', 6, 2)->default(18);
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
            $table->unique(['program_enrollment_id', 'academic_period_id']);
        });

        Schema::create('holds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('reason');
            $table->boolean('blocks_registration')->default(false);
            $table->boolean('blocks_transcript')->default(false);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        Schema::create('registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('term_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('section_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('enrolled')->index();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('dropped_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->foreignId('override_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('override_reason')->nullable();
            $table->string('moodle_enrollment_id')->nullable();
            $table->timestamps();
            $table->unique(['term_enrollment_id', 'section_id']);
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('moodle_percent', 6, 2)->nullable();
            $table->string('proposed_symbol', 12)->nullable();
            $table->string('official_symbol', 12)->nullable();
            $table->decimal('grade_points', 5, 3)->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
        });

        Schema::create('grade_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('grade_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->string('previous_symbol', 12)->nullable();
            $table->string('new_symbol', 12);
            $table->text('reason');
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('changed_at');
            $table->timestamps();
            $table->unique(['grade_id', 'revision']);
        });

        Schema::create('credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_enrollment_id')->constrained()->restrictOnDelete();
            $table->string('credential_number')->unique();
            $table->string('status')->default('pending');
            $table->date('awarded_on')->nullable();
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('academic_period_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('reversal_of_id')->nullable();
            $table->string('type');
            $table->string('reference')->unique();
            $table->string('currency', 3);
            $table->string('description');
            $table->string('status')->default('posted');
            $table->date('effective_on');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->foreign('reversal_of_id')->references('id')->on('ledger_transactions')->restrictOnDelete();
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ledger_transaction_id')->constrained()->cascadeOnDelete();
            $table->string('account_code');
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->timestamps();
            $table->index(['account_code', 'ledger_transaction_id']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('academic_period_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('currency', 3);
            $table->decimal('total', 20, 4);
            $table->decimal('balance', 20, 4);
            $table->date('issued_on');
            $table->date('due_on')->nullable();
            $table->string('status')->default('open');
            $table->json('snapshot');
            $table->timestamps();
        });

        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider');
            $table->string('provider_reference')->nullable()->unique();
            $table->string('idempotency_key')->unique();
            $table->string('currency', 3);
            $table->decimal('amount', 20, 4);
            $table->string('status')->default('pending');
            $table->string('checkout_url')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('transcript_issues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained()->restrictOnDelete();
            $table->string('serial_number')->unique();
            $table->string('verification_token', 96)->unique();
            $table->string('document_sha256', 64)->nullable();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('issued_at');
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('integration_outbox', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('connector');
            $table->string('event_type');
            $table->string('aggregate_type');
            $table->uuid('aggregate_id');
            $table->string('idempotency_key')->unique();
            $table->json('payload');
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('integration_manifest', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('connector');
            $table->string('sis_type');
            $table->uuid('sis_id');
            $table->string('external_type');
            $table->string('external_id');
            $table->string('external_idnumber')->nullable();
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['connector', 'sis_type', 'sis_id']);
            $table->unique(['connector', 'external_type', 'external_id']);
        });

        Schema::create('import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('source_filename');
            $table->string('status')->default('uploaded');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('committed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->json('reconciliation')->nullable();
            $table->timestamps();
        });

        Schema::create('import_rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('import_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('external_key')->nullable();
            $table->json('payload');
            $table->json('errors')->nullable();
            $table->string('status')->default('pending');
            $table->uuid('created_record_id')->nullable();
            $table->timestamps();
            $table->unique(['import_batch_id', 'row_number']);
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type');
            $table->string('subject_id');
            $table->string('reason')->nullable();
            $table->string('correlation_id', 64)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('integration_manifest');
        Schema::dropIfExists('integration_outbox');
        Schema::dropIfExists('transcript_issues');
        Schema::dropIfExists('payment_attempts');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_transactions');
        Schema::dropIfExists('credentials');
        Schema::dropIfExists('grade_revisions');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('holds');
        Schema::dropIfExists('term_enrollments');
        Schema::dropIfExists('program_enrollments');
        Schema::dropIfExists('application_reviews');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('section_meetings');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('grade_scale_items');
        Schema::dropIfExists('grading_scales');
        Schema::dropIfExists('academic_periods');
        Schema::dropIfExists('curriculum_courses');
        Schema::dropIfExists('course_versions');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('curriculum_versions');
        Schema::dropIfExists('role_assignments');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('people');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('campuses');
        Schema::dropIfExists('organizations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'mfa_required', 'last_login_at']);
        });
    }
};
