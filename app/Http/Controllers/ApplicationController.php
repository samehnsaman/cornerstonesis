<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Program;
use App\Services\AdmissionsService;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'program_id' => ['required', 'exists:programs,id'], 'intake_period_id' => ['required', 'exists:academic_periods,id'],
            'address' => ['nullable', 'string', 'max:500'], 'nationality' => ['nullable', 'string', 'max:100'],
            'education_level' => ['nullable', 'string', 'max:100'], 'statement' => ['nullable', 'string', 'max:3000'],
        ]);
        $person = $request->user()->person()->firstOrFail();
        $application = Application::create([
            'program_id' => $data['program_id'], 'intake_period_id' => $data['intake_period_id'], 'person_id' => $person->id,
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
        $data = $request->validate(['address' => ['required', 'string', 'max:500'], 'nationality' => ['required', 'string', 'max:100'], 'education_level' => ['required', 'string', 'max:100'], 'statement' => ['nullable', 'string', 'max:3000']]);
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
        abort_unless($document->application->person->user_id === $request->user()->id || $request->user()->hasPermission('applications.review'), 403);
        $audit->record('application.document_accessed', $document, metadata: ['application_id' => $document->application_id]);
        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function submit(Request $request, Application $application, AdmissionsService $service): RedirectResponse
    {
        $this->assertOwner($request, $application); $service->submit($application);
        return back()->with('success', __('admissions.submitted'));
    }

    public function review(Request $request, Application $application, AdmissionsService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('applications.review'), 403);
        $data = $request->validate(['recommendation' => ['required', 'in:offer,deny,waitlist,more_information'], 'notes' => ['nullable', 'string', 'max:3000'], 'checklist' => ['required', 'array'], 'checklist.identity' => ['required', 'boolean'], 'checklist.academic_records' => ['required', 'boolean'], 'checklist.eligibility' => ['required', 'boolean']]);
        $service->review($application, $data['checklist'], $data['recommendation'], $data['notes'] ?? null);
        return back()->with('success', __('admissions.reviewed'));
    }

    public function decide(Request $request, Application $application, AdmissionsService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('applications.decide'), 403);
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
}
