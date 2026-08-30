<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\AdmissionCycle;
use App\Models\Application;
use App\Models\ApplicationForm;
use App\Models\ApplicationFormField;
use App\Models\ApplicationFormSection;
use App\Models\ApplicationFormVersion;
use App\Models\AuditEvent;
use App\Models\Campus;
use App\Models\Course;
use App\Models\CourseVersion;
use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\Organization;
use App\Models\PendingMatriculation;
use App\Models\Person;
use App\Models\Program;
use App\Models\Role;
use App\Models\Room;
use App\Models\Section;
use App\Models\User;
use App\Services\AuditService;
use App\Services\MatriculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public const PERMISSIONS = [
        'admin.access', 'college.manage', 'catalog.view', 'catalog.manage', 'catalog.publish',
        'sections.manage', 'people.view', 'people.manage', 'identity.manage', 'roles.manage',
        'admissions.configure', 'applications.review', 'applications.decide', 'matriculation.approve', 'audit.view',
    ];

    public function dashboard(): Response
    {
        $organization = Organization::first();
        $checks = [
            'college' => (bool) $organization,
            'campus' => Campus::where('active', true)->exists(),
            'department' => Department::where('active', true)->exists(),
            'program' => Program::where('active', true)->exists(),
            'published_curriculum' => CurriculumVersion::whereIn('status', ['published', 'active'])->exists(),
            'admission_cycle' => AdmissionCycle::whereIn('status', ['scheduled', 'open'])->exists(),
        ];

        return $this->page('dashboard', [
            'metrics' => [
                'campuses' => Campus::count(), 'programs' => Program::count(), 'courses' => Course::count(),
                'staff' => Person::whereNotNull('staff_number')->count(), 'openCycles' => AdmissionCycle::where('status', 'open')->count(),
                'pendingMatriculations' => PendingMatriculation::where('status', 'pending')->count(),
            ],
            'setup' => $checks,
        ]);
    }

    public function college(): Response
    {
        return $this->page('college', [
            'organization' => Organization::first(),
            'campuses' => Campus::orderBy('code')->get(),
            'departments' => Department::orderBy('code')->get(),
            'rooms' => Room::orderBy('code')->get(),
        ]);
    }

    public function updateCollege(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'], 'name_en' => ['required', 'string', 'max:200'], 'name_ar' => ['nullable', 'string', 'max:200'],
            'legal_name_en' => ['nullable', 'string', 'max:200'], 'legal_name_ar' => ['nullable', 'string', 'max:200'],
            'timezone' => ['required', 'timezone'], 'default_locale' => ['required', 'in:en,ar'], 'default_currency' => ['required', 'size:3'],
            'supported_currencies' => ['required', 'array', 'min:1'], 'supported_currencies.*' => ['size:3'],
            'email' => ['nullable', 'email'], 'phone' => ['nullable', 'string', 'max:32'], 'address_en' => ['nullable', 'string'], 'address_ar' => ['nullable', 'string'],
        ]);
        $organization = Organization::first();
        $before = $organization?->toArray() ?? [];
        $organization ??= new Organization(['is_poc' => true]);
        $organization->fill($data)->save();
        $audit->record('college.updated', $organization, $before, $organization->fresh()->toArray());

        return back()->with('success', __('admin.saved'));
    }

    public function storeCampus(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20'], 'name_en' => ['required', 'string', 'max:200'], 'name_ar' => ['nullable', 'string', 'max:200'], 'timezone' => ['required', 'timezone']]);
        $organization = Organization::firstOrFail();
        $campus = Campus::create([...$data, 'organization_id' => $organization->id, 'active' => true]);
        $audit->record('campus.created', $campus, after: $campus->toArray());

        return back()->with('success', __('admin.saved'));
    }

    public function storeDepartment(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['campus_id' => ['nullable', 'exists:campuses,id'], 'code' => ['required', 'string', 'max:20'], 'name_en' => ['required', 'string', 'max:200'], 'name_ar' => ['nullable', 'string', 'max:200']]);
        $department = Department::create([...$data, 'organization_id' => Organization::firstOrFail()->id, 'active' => true]);
        $audit->record('department.created', $department, after: $department->toArray());

        return back()->with('success', __('admin.saved'));
    }

    public function storeRoom(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['campus_id' => ['required', 'exists:campuses,id'], 'code' => ['required', 'string', 'max:30'], 'name_en' => ['required', 'string', 'max:200'], 'name_ar' => ['nullable', 'string', 'max:200'], 'capacity' => ['required', 'integer', 'min:1', 'max:10000']]);
        $room = Room::create([...$data, 'active' => true]);
        $audit->record('room.created', $room, after: $room->toArray());

        return back()->with('success', __('admin.saved'));
    }

    public function archive(Request $request, string $type, string $id, AuditService $audit): RedirectResponse
    {
        $model = match ($type) {
            'campus' => Campus::class, 'department' => Department::class, 'room' => Room::class, 'program' => Program::class, 'course' => Course::class, default => abort(404)
        };
        $record = $model::findOrFail($id);
        $before = $record->toArray();
        $record->update(array_key_exists('active', $record->getAttributes()) ? ['active' => false] : ['status' => 'archived']);
        $audit->record($type.'.archived', $record, $before, $record->fresh()->toArray(), $request->validate(['reason' => ['required', 'string', 'max:1000']])['reason']);

        return back()->with('success', __('admin.archived'));
    }

    public function catalog(): Response
    {
        return $this->page('catalog', [
            'departments' => Department::where('active', true)->orderBy('code')->get(),
            'programs' => Program::with('curriculumVersions')->orderBy('code')->get(),
            'courses' => Course::with('versions')->orderBy('code')->get(),
            'faculty' => Person::where('instructor_eligible', true)->where('employment_status', 'active')->orderBy('family_name')->get(),
        ]);
    }

    public function storeProgram(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['department_id' => ['required', 'exists:departments,id'], 'code' => ['required', 'string', 'max:30', 'unique:programs,code'], 'name_en' => ['required', 'string', 'max:200'], 'name_ar' => ['nullable', 'string', 'max:200'], 'description_en' => ['nullable', 'string'], 'description_ar' => ['nullable', 'string'], 'award_type' => ['required', 'in:certificate,diploma,associate,bachelor,master,doctorate'], 'required_credits' => ['required', 'numeric', 'min:0'], 'duration_terms' => ['nullable', 'integer', 'min:1']]);
        $this->authorizeDepartment($request, 'catalog.manage', $data['department_id']);
        $program = Program::create([...$data, 'active' => true, 'status' => 'draft']);
        $audit->record('program.created', $program, after: $program->toArray());

        return back()->with('success', __('admin.saved'));
    }

    public function storeCourse(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['department_id' => ['required', 'exists:departments,id'], 'code' => ['required', 'string', 'max:30', 'unique:courses,code'], 'title_en' => ['required', 'string', 'max:200'], 'title_ar' => ['nullable', 'string', 'max:200'], 'version' => ['required', 'string', 'max:30'], 'effective_from' => ['required', 'date'], 'credit_hours' => ['required', 'numeric', 'min:0'], 'lecture_hours' => ['nullable', 'numeric', 'min:0'], 'lab_hours' => ['nullable', 'numeric', 'min:0'], 'grading_basis' => ['required', 'in:letter,pass_fail,audit'], 'description_en' => ['nullable', 'string'], 'description_ar' => ['nullable', 'string']]);
        $this->authorizeDepartment($request, 'catalog.manage', $data['department_id']);
        $course = DB::transaction(function () use ($data): Course {
            $course = Course::create([...collect($data)->only(['department_id', 'code', 'title_en', 'title_ar'])->all(), 'active' => true]);
            CourseVersion::create([...collect($data)->except(['department_id', 'code', 'title_en', 'title_ar'])->all(), 'course_id' => $course->id, 'lecture_hours' => $data['lecture_hours'] ?? 0, 'lab_hours' => $data['lab_hours'] ?? 0, 'status' => 'draft']);

            return $course;
        });
        $audit->record('course.created', $course, after: $course->load('versions')->toArray());

        return back()->with('success', __('admin.saved'));
    }

    public function storeCurriculum(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['program_id' => ['required', 'exists:programs,id'], 'version' => ['required', 'string', 'max:30'], 'effective_from' => ['required', 'date'], 'effective_to' => ['nullable', 'date', 'after:effective_from'], 'minimum_gpa' => ['required', 'numeric', 'min:0', 'max:5'], 'groups' => ['required', 'array', 'min:1'], 'groups.*.type' => ['required', 'in:required,general_education,concentration,elective'], 'groups.*.name_en' => ['required', 'string', 'max:200'], 'groups.*.name_ar' => ['nullable', 'string', 'max:200'], 'groups.*.minimum_credits' => ['required', 'numeric', 'min:0'], 'groups.*.minimum_courses' => ['required', 'integer', 'min:0'], 'groups.*.course_version_ids' => ['nullable', 'array'], 'groups.*.course_version_ids.*' => ['exists:course_versions,id']]);
        $this->authorizeProgram($request, 'catalog.manage', $data['program_id']);
        $curriculum = DB::transaction(function () use ($data): CurriculumVersion {
            $curriculum = CurriculumVersion::create([...collect($data)->except('groups')->all(), 'status' => 'draft']);
            foreach ($data['groups'] as $order => $group) {
                $groupId = (string) Str::uuid();
                DB::table('curriculum_requirement_groups')->insert(['id' => $groupId, 'curriculum_version_id' => $curriculum->id, ...collect($group)->except('course_version_ids')->all(), 'sort_order' => $order, 'created_at' => now(), 'updated_at' => now()]);
                foreach ($group['course_version_ids'] ?? [] as $sequence => $courseVersionId) {
                    DB::table('curriculum_requirement_courses')->insert(['id' => (string) Str::uuid(), 'requirement_group_id' => $groupId, 'course_version_id' => $courseVersionId, 'required' => $group['type'] === 'required', 'recommended_sequence' => $sequence + 1, 'created_at' => now(), 'updated_at' => now()]);
                }
            }

            return $curriculum;
        });
        $audit->record('curriculum.created', $curriculum, after: $curriculum->toArray());

        return back()->with('success', __('admin.saved'));
    }

    public function publish(Request $request, string $type, string $id, AuditService $audit): RedirectResponse
    {
        $model = match ($type) {
            'course-version' => CourseVersion::class, 'curriculum' => CurriculumVersion::class, default => abort(404)
        };
        $record = $model::findOrFail($id);
        abort_if(in_array($record->status, ['published', 'active'], true), 409);
        $before = $record->toArray();
        $record->update(['status' => 'published', 'published_at' => now(), 'published_by' => $request->user()->id]);
        $audit->record($type.'.published', $record, $before, $record->fresh()->toArray(), $request->validate(['reason' => ['required', 'string', 'max:1000']])['reason']);

        return back()->with('success', __('admin.published'));
    }

    public function correctCourse(Request $request, Course $course, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['title_en' => ['required', 'string', 'max:200'], 'title_ar' => ['nullable', 'string', 'max:200'], 'description_en' => ['nullable', 'string'], 'description_ar' => ['nullable', 'string'], 'reason' => ['required', 'string', 'max:1000']]);
        $before = $course->load('versions')->toArray();
        $course->update(collect($data)->only(['title_en', 'title_ar'])->all());
        $course->versions()->whereIn('status', ['published', 'active'])->update(collect($data)->only(['description_en', 'description_ar'])->all());
        $audit->record('course.typo_corrected', $course, $before, $course->fresh()->load('versions')->toArray(), $data['reason']);

        return back()->with('success', __('admin.saved'));
    }

    public function storeCourseCoordinator(Request $request, Course $course, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['person_id' => ['required', Rule::exists('people', 'id')->where('instructor_eligible', true)], 'starts_on' => ['nullable', 'date'], 'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on']]);
        $this->authorizeDepartment($request, 'catalog.manage', $course->department_id);
        $id = (string) Str::uuid();
        DB::table('course_coordinators')->insert(['id' => $id, 'course_id' => $course->id, ...$data, 'created_at' => now(), 'updated_at' => now()]);
        $audit->record('course.coordinator_assigned', $course, after: ['person_id' => $data['person_id'], 'starts_on' => $data['starts_on'] ?? null, 'ends_on' => $data['ends_on'] ?? null]);

        return back()->with('success', __('admin.saved'));
    }

    public function academics(): Response
    {
        return $this->page('academics', [
            'periods' => AcademicPeriod::orderByDesc('starts_on')->get(), 'campuses' => Campus::where('active', true)->get(),
            'rooms' => Room::where('active', true)->get(), 'courseVersions' => CourseVersion::with('course')->whereIn('status', ['published', 'active'])->get(),
            'faculty' => Person::where('instructor_eligible', true)->where('employment_status', 'active')->get(),
            'sections' => Section::with(['courseVersion.course', 'academicPeriod', 'campus', 'meetings'])->latest()->get(),
        ]);
    }

    public function storePeriod(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:30', 'unique:academic_periods,code'], 'name_en' => ['required', 'string', 'max:200'], 'name_ar' => ['nullable', 'string', 'max:200'], 'type' => ['required', 'in:semester,trimester,quarter,summer,short_session'], 'starts_on' => ['required', 'date'], 'ends_on' => ['required', 'date', 'after:starts_on'], 'registration_opens_at' => ['nullable', 'date'], 'registration_closes_at' => ['nullable', 'date', 'after:registration_opens_at']]);
        $period = AcademicPeriod::create([...$data, 'organization_id' => Organization::firstOrFail()->id, 'status' => 'planned']);
        $audit->record('academic_period.created', $period, after: $period->toArray());

        return back()->with('success', __('admin.saved'));
    }

    public function storeSection(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['course_version_id' => ['required', 'exists:course_versions,id'], 'academic_period_id' => ['required', 'exists:academic_periods,id'], 'campus_id' => ['required', 'exists:campuses,id'], 'code' => ['required', 'string', 'max:40'], 'capacity' => ['required', 'integer', 'min:1'], 'waitlist_capacity' => ['nullable', 'integer', 'min:0'], 'delivery_mode' => ['required', 'in:in_person,online,hybrid'], 'instructors' => ['required', 'array', 'min:1'], 'instructors.*.person_id' => ['required', 'exists:people,id'], 'instructors.*.role' => ['required', 'in:primary,secondary'], 'meetings' => ['nullable', 'array'], 'meetings.*.day_of_week' => ['required', 'integer', 'between:1,7'], 'meetings.*.starts_at' => ['required', 'date_format:H:i'], 'meetings.*.ends_at' => ['required', 'date_format:H:i', 'after:meetings.*.starts_at'], 'meetings.*.room_id' => ['nullable', 'exists:rooms,id'], 'override_reason' => ['nullable', 'string', 'max:1000']]);
        $departmentId = CourseVersion::with('course:id,department_id')->findOrFail($data['course_version_id'])->course->department_id;
        $this->authorizeDepartment($request, 'sections.manage', $departmentId, $data['campus_id']);
        $conflicts = $this->scheduleConflicts($data);
        if ($conflicts !== [] && blank($data['override_reason'] ?? null)) {
            throw ValidationException::withMessages(['meetings' => __('admin.schedule_conflict')]);
        }
        $section = DB::transaction(function () use ($data): Section {
            $section = Section::create([...collect($data)->only(['course_version_id', 'academic_period_id', 'campus_id', 'code', 'capacity', 'waitlist_capacity', 'delivery_mode'])->all(), 'waitlist_capacity' => $data['waitlist_capacity'] ?? 0, 'status' => 'planned']);
            foreach ($data['instructors'] as $instructor) {
                DB::table('section_instructors')->insert(['id' => (string) Str::uuid(), 'section_id' => $section->id, ...$instructor, 'created_at' => now(), 'updated_at' => now()]);
            }
            foreach ($data['meetings'] ?? [] as $meeting) {
                DB::table('section_meetings')->insert(['id' => (string) Str::uuid(), 'section_id' => $section->id, ...$meeting, 'created_at' => now(), 'updated_at' => now()]);
            }

            return $section;
        });
        $audit->record('section.created', $section, after: $section->toArray(), reason: $data['override_reason'] ?? null, metadata: ['conflicts' => $conflicts]);

        return back()->with('success', __('admin.saved'));
    }

    public function people(): Response
    {
        return $this->page('people', ['people' => Person::with('user')->orderBy('family_name')->get(), 'departments' => Department::where('active', true)->get(), 'roles' => Role::orderBy('name_en')->get(), 'campuses' => Campus::where('active', true)->get(), 'programs' => Program::where('active', true)->get(), 'permissions' => self::PERMISSIONS]);
    }

    public function storePerson(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['given_name' => ['required', 'string', 'max:100'], 'family_name' => ['required', 'string', 'max:100'], 'given_name_ar' => ['nullable', 'string', 'max:100'], 'family_name_ar' => ['nullable', 'string', 'max:100'], 'email' => ['nullable', 'email'], 'phone' => ['nullable', 'string', 'max:32'], 'department_id' => ['nullable', 'exists:departments,id'], 'staff_number' => ['required', 'string', 'max:50', 'unique:people,staff_number'], 'instructor_eligible' => ['boolean']]);
        if ($data['department_id'] ?? null) {
            $this->authorizeDepartment($request, 'people.manage', $data['department_id']);
        }
        $person = Person::create([...$data, 'external_id' => 'SISPOC-STAFF-'.Str::upper(Str::random(8)), 'employment_status' => 'active', 'status' => 'active']);
        $audit->record('person.created', $person, after: $person->toArray());

        return back()->with('success', __('admin.saved'));
    }

    public function activateAccount(Request $request, Person $person, AuditService $audit): RedirectResponse
    {
        abort_if($person->user_id, 409);
        $data = $request->validate(['email' => ['required', 'email', 'unique:users,email'], 'role_id' => ['required', 'exists:roles,id'], 'campus_id' => ['nullable', 'exists:campuses,id'], 'department_id' => ['nullable', 'exists:departments,id'], 'program_id' => ['nullable', 'exists:programs,id']]);
        $role = Role::findOrFail($data['role_id']);
        $scope = collect($data)->only(['campus_id', 'department_id', 'program_id'])->filter()->all();
        abort_unless($this->canGrantRole($request->user(), $role, $scope), 403);
        $temporary = Str::password(18, true, true, true, false);
        $recoveryCodes = collect(range(1, 8))->map(fn () => Str::upper(Str::random(5).'-'.Str::random(5)));
        DB::transaction(function () use ($person, $data, $role, $temporary, $recoveryCodes, $request): void {
            $user = User::create(['name' => $person->displayName(), 'email' => $data['email'], 'password' => Hash::make($temporary), 'email_verified_at' => now(), 'locale' => $person->locale, 'status' => 'active', 'must_change_password' => true, 'mfa_required' => $role->privileged, 'mfa_recovery_codes' => $recoveryCodes->map(fn ($code) => Hash::make($code))->all()]);
            $person->update(['user_id' => $user->id, 'email' => $data['email']]);
            DB::table('role_assignments')->insert(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role_id' => $role->id, 'campus_id' => $data['campus_id'] ?? null, 'department_id' => $data['department_id'] ?? null, 'program_id' => $data['program_id'] ?? null, 'assigned_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        });
        $audit->record('account.activated', $person, after: ['email' => $data['email'], 'role_id' => $role->id]);

        return back()->with('success', __('admin.account_activated'))->with('temporary_password', $temporary)->with('temporary_recovery_codes', $recoveryCodes->all());
    }

    public function storeRole(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'alpha_dash', 'max:50', 'unique:roles,code'], 'name_en' => ['required', 'string', 'max:100'], 'name_ar' => ['nullable', 'string', 'max:100'], 'permissions' => ['required', 'array', 'min:1'], 'permissions.*' => [Rule::in(self::PERMISSIONS)], 'privileged' => ['boolean']]);
        abort_unless(collect($data['permissions'])->every(fn ($permission) => $request->user()->hasPermission('*') || $request->user()->hasPermission($permission)), 403);
        $role = Role::create($data);
        $audit->record('role.created', $role, after: $role->toArray());

        return back()->with('success', __('admin.saved'));
    }

    public function admissions(): Response
    {
        return $this->page('admissions', [
            'forms' => ApplicationForm::with('versions.sections.fields')->orderBy('name_en')->get(),
            'cycles' => AdmissionCycle::orderByDesc('opens_at')->get(), 'programs' => Program::where('active', true)->get(),
            'campuses' => Campus::where('active', true)->get(), 'periods' => AcademicPeriod::orderByDesc('starts_on')->get(),
            'applications' => Application::with(['person', 'program', 'intakePeriod'])->latest()->limit(200)->get(),
        ]);
    }

    public function storeForm(Request $request, AuditService $audit): RedirectResponse
    {
        $types = ['text', 'long_text', 'number', 'date', 'choice', 'multi_choice', 'yes_no', 'address', 'education_history', 'document', 'declaration', 'consent'];
        $data = $request->validate(['code' => ['required', 'alpha_dash', 'max:50', 'unique:application_forms,code'], 'name_en' => ['required', 'string', 'max:200'], 'name_ar' => ['nullable', 'string', 'max:200'], 'sections' => ['required', 'array', 'min:1'], 'sections.*.title_en' => ['required', 'string', 'max:200'], 'sections.*.title_ar' => ['nullable', 'string', 'max:200'], 'sections.*.fields' => ['required', 'array', 'min:1'], 'sections.*.fields.*.key' => ['required', 'alpha_dash', 'max:80'], 'sections.*.fields.*.type' => ['required', Rule::in($types)], 'sections.*.fields.*.label_en' => ['required', 'string', 'max:200'], 'sections.*.fields.*.label_ar' => ['nullable', 'string', 'max:200'], 'sections.*.fields.*.required' => ['boolean'], 'sections.*.fields.*.options' => ['nullable', 'array'], 'sections.*.fields.*.visibility_rules' => ['nullable', 'array']]);
        $form = DB::transaction(function () use ($data): ApplicationForm {
            $form = ApplicationForm::create([...collect($data)->only(['code', 'name_en', 'name_ar'])->all(), 'active' => true]);
            $version = ApplicationFormVersion::create(['application_form_id' => $form->id, 'version' => 1, 'status' => 'draft']);
            foreach ($data['sections'] as $sectionOrder => $sectionData) {
                $section = ApplicationFormSection::create([...collect($sectionData)->only(['title_en', 'title_ar'])->all(), 'form_version_id' => $version->id, 'sort_order' => $sectionOrder]);
                foreach ($sectionData['fields'] as $fieldOrder => $field) {
                    ApplicationFormField::create([...$field, 'form_section_id' => $section->id, 'sort_order' => $fieldOrder]);
                }
            }

            return $form;
        });
        $audit->record('application_form.created', $form, after: $form->load('versions.sections.fields')->toArray());

        return back()->with('success', __('admin.saved'));
    }

    public function publishForm(Request $request, ApplicationFormVersion $version, AuditService $audit): RedirectResponse
    {
        abort_unless($version->status === 'draft' && $version->sections()->whereHas('fields')->exists(), 409);
        $reason = $request->validate(['reason' => ['required', 'string', 'max:1000']])['reason'];
        $before = $version->toArray();
        $version->update(['status' => 'published', 'published_at' => now(), 'published_by' => $request->user()->id]);
        $audit->record('application_form.published', $version, $before, $version->fresh()->toArray(), $reason);

        return back()->with('success', __('admin.published'));
    }

    public function storeCycle(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['program_id' => ['required', 'exists:programs,id'], 'campus_id' => ['required', 'exists:campuses,id'], 'intake_period_id' => ['required', 'exists:academic_periods,id'], 'form_version_id' => ['required', Rule::exists('application_form_versions', 'id')->where('status', 'published')], 'code' => ['required', 'string', 'max:50', 'unique:admission_cycles,code'], 'name_en' => ['required', 'string', 'max:200'], 'name_ar' => ['nullable', 'string', 'max:200'], 'quota' => ['nullable', 'integer', 'min:1'], 'opens_at' => ['required', 'date'], 'closes_at' => ['required', 'date', 'after:opens_at'], 'decision_deadline' => ['nullable', 'date'], 'acceptance_deadline' => ['nullable', 'date'], 'application_fee' => ['required', 'numeric', 'min:0'], 'currency' => ['required', 'size:3'], 'required_documents' => ['nullable', 'array'], 'required_documents.*' => ['string', 'max:80']]);
        $this->authorizeProgram($request, 'admissions.configure', $data['program_id'], $data['campus_id']);
        $cycle = AdmissionCycle::create([...$data, 'status' => now()->between($data['opens_at'], $data['closes_at']) ? 'open' : 'scheduled']);
        $audit->record('admission_cycle.created', $cycle, after: $cycle->toArray());

        return back()->with('success', __('admin.saved'));
    }

    public function matriculations(): Response
    {
        return $this->page('matriculation', ['pending' => PendingMatriculation::with(['application.person', 'application.program', 'application.intakePeriod'])->latest()->get(), 'campuses' => Campus::where('active', true)->get(), 'periods' => AcademicPeriod::orderByDesc('starts_on')->get(), 'curricula' => CurriculumVersion::whereIn('status', ['published', 'active'])->get()]);
    }

    public function approveMatriculation(Request $request, PendingMatriculation $pending, MatriculationService $service): RedirectResponse
    {
        $application = $pending->application()->firstOrFail();
        $data = $request->validate(['curriculum_version_id' => [Rule::exists('curriculum_versions', 'id')->where('program_id', $application->program_id)], 'campus_id' => ['required', 'exists:campuses,id'], 'intake_period_id' => ['required', 'exists:academic_periods,id'], 'started_on' => ['required', 'date'], 'create_term_enrollment' => ['required', 'boolean'], 'credit_limit' => ['nullable', 'numeric', 'min:0'], 'override_reason' => ['nullable', 'string', 'max:1000']]);
        $this->authorizeProgram($request, 'matriculation.approve', $application->program_id, $data['campus_id']);
        $enrollment = $service->approve($pending, $data);

        return back()->with('success', __('admin.matriculated', ['number' => $enrollment->student_number]));
    }

    public function audit(): Response
    {
        return $this->page('audit', ['events' => AuditEvent::with('actor:id,name,email')->latest('occurred_at')->paginate(100)]);
    }

    private function page(string $module, array $data): Response
    {
        return Inertia::render('Admin/Portal', ['module' => $module, ...$data]);
    }

    private function canGrantRole(User $actor, Role $role, array $scope): bool
    {
        return collect($role->permissions)->every(fn ($permission) => $actor->hasPermission('*', $scope) || $actor->hasPermission($permission, $scope));
    }

    private function authorizeDepartment(Request $request, string $permission, string $departmentId, ?string $campusId = null): void
    {
        $department = Department::findOrFail($departmentId);
        abort_unless($request->user()->hasPermission($permission, ['department_id' => $department->id, 'campus_id' => $campusId ?? $department->campus_id]), 403);
    }

    private function authorizeProgram(Request $request, string $permission, string $programId, ?string $campusId = null): void
    {
        $program = Program::with('department')->findOrFail($programId);
        abort_unless($request->user()->hasPermission($permission, ['program_id' => $program->id, 'department_id' => $program->department_id, 'campus_id' => $campusId ?? $program->department->campus_id]), 403);
    }

    private function scheduleConflicts(array $data): array
    {
        $conflicts = [];
        foreach ($data['meetings'] ?? [] as $meeting) {
            $overlap = DB::table('section_meetings')->join('sections', 'sections.id', '=', 'section_meetings.section_id')
                ->where('sections.academic_period_id', $data['academic_period_id'])->where('section_meetings.day_of_week', $meeting['day_of_week'])
                ->where('section_meetings.starts_at', '<', $meeting['ends_at'])->where('section_meetings.ends_at', '>', $meeting['starts_at']);
            if (! empty($meeting['room_id']) && (clone $overlap)->where('section_meetings.room_id', $meeting['room_id'])->exists()) {
                $conflicts[] = 'room';
            }
            $personIds = collect($data['instructors'])->pluck('person_id');
            if ((clone $overlap)->join('section_instructors', 'section_instructors.section_id', '=', 'sections.id')->whereIn('section_instructors.person_id', $personIds)->exists()) {
                $conflicts[] = 'faculty';
            }
        }

        return array_values(array_unique($conflicts));
    }
}
