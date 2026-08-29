<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\IntegrationOutbox;
use App\Models\Registration;
use App\Models\Section;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', ['metrics' => [
            'applications' => Application::count(), 'sections' => Section::count(),
            'registrations' => Registration::where('status', 'enrolled')->count(),
            'integrationIssues' => IntegrationOutbox::whereIn('status', ['retrying', 'dead_letter'])->count(),
        ]]);
    }
}
