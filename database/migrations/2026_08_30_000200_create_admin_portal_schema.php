<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('status')->default('active')->index();
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('mfa_verified_at')->nullable();
            $table->json('mfa_recovery_codes')->nullable();
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('legal_name_en')->nullable();
            $table->string('legal_name_ar')->nullable();
            $table->string('default_currency', 3)->default('USD');
            $table->json('supported_currencies')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->text('address_en')->nullable();
            $table->text('address_ar')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('transcript_branding')->nullable();
        });

        Schema::table('programs', function (Blueprint $table): void {
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->unsignedSmallInteger('duration_terms')->nullable();
            $table->string('status')->default('draft')->index();
        });

        Schema::table('course_versions', function (Blueprint $table): void {
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('grading_basis')->default('letter');
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('curriculum_versions', function (Blueprint $table): void {
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('people', function (Blueprint $table): void {
            $table->foreignUuid('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('staff_number')->nullable()->unique();
            $table->string('employment_status')->default('active')->index();
            $table->boolean('instructor_eligible')->default(false);
        });

        Schema::table('role_assignments', function (Blueprint $table): void {
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('rooms', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('campus_id')->constrained()->restrictOnDelete();
            $table->string('code');
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->unsignedInteger('capacity')->default(1);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['campus_id', 'code']);
        });

        Schema::table('section_meetings', function (Blueprint $table): void {
            $table->foreignUuid('room_id')->nullable()->constrained('rooms')->nullOnDelete();
        });

        Schema::create('curriculum_requirement_groups', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('curriculum_version_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->decimal('minimum_credits', 8, 2)->default(0);
            $table->unsignedSmallInteger('minimum_courses')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('curriculum_requirement_courses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('requirement_group_id')->constrained('curriculum_requirement_groups')->cascadeOnDelete();
            $table->foreignUuid('course_version_id')->constrained()->restrictOnDelete();
            $table->boolean('required')->default(false);
            $table->unsignedSmallInteger('recommended_sequence')->nullable();
            $table->timestamps();
            $table->unique(['requirement_group_id', 'course_version_id']);
        });

        Schema::create('course_coordinators', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('person_id')->constrained('people')->restrictOnDelete();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'person_id', 'starts_on']);
        });

        Schema::create('section_instructors', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('section_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('person_id')->constrained('people')->restrictOnDelete();
            $table->string('role')->default('secondary');
            $table->timestamps();
            $table->unique(['section_id', 'person_id']);
        });

        Schema::create('application_forms', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('application_form_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_form_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['application_form_id', 'version']);
        });

        Schema::create('application_form_sections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('form_version_id')->constrained('application_form_versions')->cascadeOnDelete();
            $table->string('title_en');
            $table->string('title_ar')->nullable();
            $table->text('help_en')->nullable();
            $table->text('help_ar')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('application_form_fields', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('form_section_id')->constrained('application_form_sections')->cascadeOnDelete();
            $table->string('key');
            $table->string('type');
            $table->string('label_en');
            $table->string('label_ar')->nullable();
            $table->text('help_en')->nullable();
            $table->text('help_ar')->nullable();
            $table->boolean('required')->default(false);
            $table->json('validation_rules')->nullable();
            $table->json('options')->nullable();
            $table->json('visibility_rules')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['form_section_id', 'key']);
        });

        Schema::create('admission_cycles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('campus_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('intake_period_id')->constrained('academic_periods')->restrictOnDelete();
            $table->foreignUuid('form_version_id')->constrained('application_form_versions')->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->unsignedInteger('quota')->nullable();
            $table->timestamp('opens_at');
            $table->timestamp('closes_at');
            $table->date('decision_deadline')->nullable();
            $table->date('acceptance_deadline')->nullable();
            $table->decimal('application_fee', 20, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->json('required_documents')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });

        Schema::table('applications', function (Blueprint $table): void {
            $table->foreignUuid('admission_cycle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('form_version_id')->nullable()->constrained('application_form_versions')->nullOnDelete();
            $table->json('form_snapshot')->nullable();
            $table->timestamp('offer_expires_at')->nullable();
        });

        Schema::create('pending_matriculations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignUuid('curriculum_version_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignUuid('campus_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignUuid('intake_period_id')->nullable()->constrained('academic_periods')->restrictOnDelete();
            $table->string('status')->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('program_enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('create_term_enrollment')->default(false);
            $table->text('override_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('student_number_sequences', function (Blueprint $table): void {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::create('mfa_challenges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'expires_at']);
        });

        DB::table('applications')->whereNull('form_snapshot')->update([
            'form_snapshot' => json_encode(['type' => 'legacy', 'version' => 1]),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_challenges');
        Schema::dropIfExists('student_number_sequences');
        Schema::dropIfExists('pending_matriculations');
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('admission_cycle_id');
            $table->dropConstrainedForeignId('form_version_id');
            $table->dropColumn(['form_snapshot', 'offer_expires_at']);
        });
        Schema::dropIfExists('admission_cycles');
        Schema::dropIfExists('application_form_fields');
        Schema::dropIfExists('application_form_sections');
        Schema::dropIfExists('application_form_versions');
        Schema::dropIfExists('application_forms');
        Schema::dropIfExists('section_instructors');
        Schema::dropIfExists('course_coordinators');
        Schema::dropIfExists('curriculum_requirement_courses');
        Schema::dropIfExists('curriculum_requirement_groups');
        Schema::table('section_meetings', fn (Blueprint $table) => $table->dropConstrainedForeignId('room_id'));
        Schema::dropIfExists('rooms');
        Schema::table('role_assignments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('revoked_by');
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropColumn('revoked_at');
        });
        Schema::table('people', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['staff_number', 'employment_status', 'instructor_eligible']);
        });
        Schema::table('curriculum_versions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn('published_at');
        });
        Schema::table('course_versions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn(['description_en', 'description_ar', 'grading_basis', 'status', 'published_at']);
        });
        Schema::table('programs', fn (Blueprint $table) => $table->dropColumn(['description_en', 'description_ar', 'duration_terms', 'status']));
        Schema::table('organizations', fn (Blueprint $table) => $table->dropColumn(['legal_name_en', 'legal_name_ar', 'default_currency', 'supported_currencies', 'email', 'phone', 'address_en', 'address_ar', 'logo_path', 'transcript_branding']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['status', 'must_change_password', 'mfa_verified_at', 'mfa_recovery_codes']));
    }
};
