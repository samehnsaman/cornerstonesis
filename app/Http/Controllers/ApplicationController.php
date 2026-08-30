<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\AdmissionCycle;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationFormVersion;
use App\Models\Program;
use App\Services\AdmissionsService;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        $canReview = $request->user()->hasPermission('applications.review');
        $canDecide = $request->user()->hasPermission('applications.decide');
        $query = Application::with([
            'program:id,code,name_en,name_ar', 'intakePeriod:id,code,name_en,name_ar',
            'person:id,given_name,family_name,user_id', 'documents', 'reviews.reviewer:id,name',
        ])->latest();

        if (! $canReview && ! $canDecide) {
            $query->whereHas('person', fn ($q) => $q->where('user_id', $request->user()->id));
        }

        return Inertia::render('Applications/Index', [
            'applications' => $query->get(),
            'programs' => Program::where('active', true)->get(['id', 'code', 'name_en', 'name_ar']),
            'intakes' => AcademicPeriod::whereIn('status', ['planned', 'open'])->orderBy('starts_on')->get(['id', 'code', 'name_en', 'name_ar', 'starts_on']),
            'capabilities' => ['review' => $canReview, 'decide' => $canDecide],
            'cycles' => AdmissionCycle::with(['program:id,code,name_en,name_ar'])
                ->where('status', 'open')->where('opens_at', '<=', now())->where('closes_at', '>=', now())->orderBy('closes_at')->get(),
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'admission_cycle_id' => ['nullable', 'exists:admission_cycles,id'],
            'program_id' => ['required_without:admission_cycle_id', 'exists:programs,id'], 'intake_period_id' => ['required_without:admission_cycle_id', 'exists:academic_periods,id'],
            'address' => ['nullable', 'string', 'max:500'], 'nationality' => ['nullable', 'string', 'max:100'],
            'education_level' => ['nullable', 'string', 'max:100'], 'statement' => ['nullable', 'string', 'max:3000'],
        ]);
        $person = $request->user()->person()->firstOrFail();
        $cycle = isset($data['admission_cycle_id']) ? AdmissionCycle::findOrFail($data['admission_cycle_id']) : null;
        if ($cycle) {
            abort_unless($cycle->status === 'open' && now()->between($cycle->opens_at, $cycle->closes_at), 409);
        }
        $formSnapshot = $cycle ? ApplicationFormVersion::with('sections.fields')->findOrFail($cycle->form_version_id)->toArray() : ['type' => 'legacy', 'version' => 1];
        $application = Application::create([
            'program_id' => $cycle?->program_id ?? $data['program_id'], 'intake_period_id' => $cycle?->intake_period_id ?? $data['intake_period_id'], 'person_id' => $person->id,
            'admission_cycle_id' => $cycle?->id, 'form_version_id' => $cycle?->form_version_id, 'form_snapshot' => $formSnapshot,
            'application_number' => 'A'.now()->format('Y').Str::upper(Str::random(7)), 'status' => 'draft',
            'form_data' => collect($data)->except(['program_id', 'intake_period_id'])->all(),
        ]);
        $audit->record('application.created', $application, after: $application->toArray());

        return back()->with('success', __('app.saved'));
    }

    public function update(Request $request, Application $application, AuditService $audit): RedirectResponse
    {
        $this->assertOwner($request, $application);
        abort_unless($application->status === 'draft', 409, __('admissions.draft_only'));
        $rules = ['address' => ['required', 'string', 'max:500'], 'nationality' => ['required', 'string', 'max:100'], 'education_level' => ['required', 'string', 'max:100'], 'statement' => ['nullable', 'string', 'max:3000']];
        foreach (collect($application->form_snapshot['sections'] ?? [])->flatMap(fn (array $section) => $section['fields'] ?? []) as $field) {
            if (isset($rules[$field['key']]) || $field['type'] === 'document') {
                continue;
            }
            $visible = collect($field['visibility_rules'] ?? [])->every(fn ($condition) => ($request->input($condition['field'] ?? '') === ($condition['value'] ?? null)));
            $fieldRules = [($field['required'] ?? false) && $visible ? 'required' : 'nullable'];
            $fieldRules[] = match ($field['type']) {
                'number' => 'numeric', 'date' => 'date', 'yes_no', 'consent', 'declaration' => 'boolean',
                'multi_choice', 'address', 'education_history' => 'array', default => 'string',
            };
            if ($field['type'] === 'choice' && ! empty($field['options'])) {
                $fieldRules[] = Rule::in(collect($field['options'])->map(fn ($option) => is_array($option) ? $option['value'] : $option)->all());
            }
            $rules[$field['key']] = $fieldRules;
        }
        $data = $request->validate($rules);
        $before = $application->toArray();
        $application->update(['form_data' => $data]);
        $audit->record('application.updated', $application, $before, $application->fresh()->toArray());

        return back()->with('success', __('app.saved'));
    }

    public function upload(Request $request, Application $application, AuditService $audit): RedirectResponse
    {
        $this->assertOwner($request, $application);
        abort_unless(in_array($application->status, ['draft', 'submitted'], true), 409);
        $data = $request->validate(['type' => ['required', 'in:identity,transcript,certificate,language,other'], 'document' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png']]);
        $file = $data['document'];
        $path = $file->store("admissions/{$application->id}", 'local');
        $document = $application->documents()->create(['type' => $data['type'], 'disk' => 'local', 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath()), 'verification_status' => 'pending']);
        $audit->record('application.document_uploaded', $document, after: ['application_id' => $application->id, 'type' => $document->type, 'sha256' => $document->sha256]);

        return back()->with('success', __('admissions.document_uploaded'));
    }

    public function download(Request $request, ApplicationDocument $document, AuditService $audit): StreamedResponse
    {
        $document->loadMissing('application.person');
        abort_unless($document->application->person->user_id === $request->user()->id || $request->user()->hasPermission('applications.review', $this->scope($document->application)), 403);
        $audit->record('application.document_accessed', $document, metadata: ['application_id' => $document->application_id]);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function submit(Request $request, Application $application, AdmissionsService $service): RedirectResponse
    {
        $this->assertOwner($request, $application);
        $service->submit($application);

        return back()->with('success', __('admissions.submitted'));
    }

    public function review(Request $request, Application $application, AdmissionsService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('applications.review', $this->scope($application)), 403);
        $data = $request->validate(['recommendation' => ['required', 'in:offer,deny,waitlist,more_information'], 'notes' => ['nullable', 'string', 'max:3000'], 'checklist' => ['required', 'array'], 'checklist.identity' => ['required', 'boolean'], 'checklist.academic_records' => ['required', 'boolean'], 'checklist.eligibility' => ['required', 'boolean']]);
        $service->review($application, $data['checklist'], $data['recommendation'], $data['notes'] ?? null);

        return back()->with('success', __('admissions.reviewed'));
    }

    public function decide(Request $request, Application $application, AdmissionsService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('applications.decide', $this->scope($application)), 403);
        $data = $request->validate(['decision' => ['required', 'in:offered,denied,waitlisted'], 'reason' => ['required', 'string', 'max:2000'], 'conditions' => ['nullable', 'array'], 'conditions.*' => ['string', 'max:500']]);
        $service->decide($application, $data['decision'], $data['reason'], $data['conditions'] ?? []);

        return back()->with('success', __('admissions.decided'));
    }

    public function respond(Request $request, Application $application, AdmissionsService $service): RedirectResponse
    {
        $this->assertOwner($request, $application);
        $response = $request->validate(['response' => ['required', 'in:accepted,declined']])['response'];
        $service->respondToOffer($application, $response);

        return back()->with('success', __('admissions.response_saved'));
    }

    private function assertOwner(Request $request, Application $application): void
    {
        $application->loadMissing('person');
        abort_unless($application->person->user_id === $request->user()->id, 403);
    }

    private function scope(Application $application): array
    {
        $application->loadMissing('program.department');

        return ['program_id' => $application->program_id, 'department_id' => $application->program->department_id, 'campus_id' => $application->program->department->campus_id];
    }
}
