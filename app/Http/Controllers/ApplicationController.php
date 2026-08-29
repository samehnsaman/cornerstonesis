<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Application::with(['program:id,code,name_en,name_ar', 'person:id,given_name,family_name,user_id'])->latest();
        if (! $request->user()->hasPermission('applications.review')) $query->whereHas('person', fn ($q) => $q->where('user_id', $request->user()->id));
        return Inertia::render('Applications/Index', ['applications' => $query->get(), 'programs' => Program::where('active', true)->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['program_id' => ['required', 'exists:programs,id'], 'intake_period_id' => ['required', 'exists:academic_periods,id']]);
        $person = $request->user()->person()->firstOrFail();
        Application::create([...$data, 'person_id' => $person->id, 'application_number' => 'A'.now()->format('Y').Str::upper(Str::random(7)), 'status' => 'submitted', 'submitted_at' => now()]);
        return back()->with('success', __('app.saved'));
    }
}
