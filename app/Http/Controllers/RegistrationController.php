<?php
namespace App\Http\Controllers;
use App\Models\Section; use App\Models\TermEnrollment; use App\Services\RegistrationService; use Illuminate\Http\RedirectResponse; use Illuminate\Http\Request; use Inertia\Inertia; use Inertia\Response;
class RegistrationController extends Controller {
 public function index(Request $request): Response { $person=$request->user()->person; $terms=$person?->programEnrollments()->with(['termEnrollments.registrations.section.courseVersion.course','termEnrollments.academicPeriod'])->get()??collect(); return Inertia::render('Registration/Index',['terms'=>$terms,'sections'=>Section::with(['courseVersion.course','academicPeriod'])->where('status','open')->get()]); }
 public function store(Request $request,RegistrationService $service): RedirectResponse { $data=$request->validate(['term_enrollment_id'=>['required','exists:term_enrollments,id'],'section_id'=>['required','exists:sections,id'],'override_reason'=>['nullable','string','max:500']]); $term=TermEnrollment::findOrFail($data['term_enrollment_id']); abort_unless($term->programEnrollment->person->user_id===$request->user()->id||$request->user()->hasPermission('registration.override'),403); $service->register($term,Section::findOrFail($data['section_id']),$data['override_reason']??null); return back()->with('success',__('app.saved')); }
}
