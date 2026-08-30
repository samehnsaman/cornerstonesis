<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\MfaController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\TranscriptController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::middleware('auth')->group(function (): void {
    Route::get('/mfa', [MfaController::class, 'show'])->name('mfa.challenge');
    Route::post('/mfa/send', [MfaController::class, 'send'])->name('mfa.send');
    Route::post('/mfa/verify', [MfaController::class, 'verify'])->name('mfa.verify');
    Route::get('/change-password', [PasswordChangeController::class, 'show'])->name('password.change.form');
    Route::put('/change-password', [PasswordChangeController::class, 'update'])->name('password.change.update');
    Route::post('/locale', [AuthController::class, 'locale'])->name('locale');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::middleware('staff.mfa')->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
        Route::patch('/applications/{application}', [ApplicationController::class, 'update'])->name('applications.update');
        Route::post('/applications/{application}/documents', [ApplicationController::class, 'upload'])->name('applications.documents.store');
        Route::get('/application-documents/{document}', [ApplicationController::class, 'download'])->name('applications.documents.download');
        Route::post('/applications/{application}/submit', [ApplicationController::class, 'submit'])->name('applications.submit');
        Route::post('/applications/{application}/reviews', [ApplicationController::class, 'review'])->name('applications.review');
        Route::post('/applications/{application}/decision', [ApplicationController::class, 'decide'])->name('applications.decide');
        Route::post('/applications/{application}/response', [ApplicationController::class, 'respond'])->name('applications.respond');
        Route::get('/registration', [RegistrationController::class, 'index'])->name('registration.index');
        Route::post('/registration', [RegistrationController::class, 'store'])->name('registration.store');
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::post('/transcripts/{person}', [TranscriptController::class, 'issue'])->name('transcripts.issue');

        Route::prefix('admin')->name('admin.')->middleware('permission:admin.access')->group(function (): void {
            Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
            Route::get('/college', [AdminController::class, 'college'])->middleware('permission:college.manage')->name('college');
            Route::put('/college', [AdminController::class, 'updateCollege'])->middleware('permission:college.manage');
            Route::post('/campuses', [AdminController::class, 'storeCampus'])->middleware('permission:college.manage');
            Route::post('/departments', [AdminController::class, 'storeDepartment'])->middleware('permission:college.manage');
            Route::post('/rooms', [AdminController::class, 'storeRoom'])->middleware('permission:college.manage');
            Route::post('/archive/{type}/{id}', [AdminController::class, 'archive'])->middleware('permission:college.manage');
            Route::get('/catalog', [AdminController::class, 'catalog'])->middleware('permission:catalog.view')->name('catalog');
            Route::post('/programs', [AdminController::class, 'storeProgram'])->middleware('permission:catalog.manage');
            Route::post('/courses', [AdminController::class, 'storeCourse'])->middleware('permission:catalog.manage');
            Route::post('/curricula', [AdminController::class, 'storeCurriculum'])->middleware('permission:catalog.manage');
            Route::post('/publish/{type}/{id}', [AdminController::class, 'publish'])->middleware('permission:catalog.publish');
            Route::patch('/courses/{course}/correction', [AdminController::class, 'correctCourse'])->middleware('permission:catalog.publish');
            Route::post('/courses/{course}/coordinators', [AdminController::class, 'storeCourseCoordinator'])->middleware('permission:catalog.manage');
            Route::get('/academics', [AdminController::class, 'academics'])->middleware('permission:sections.manage')->name('academics');
            Route::post('/periods', [AdminController::class, 'storePeriod'])->middleware('permission:sections.manage');
            Route::post('/sections', [AdminController::class, 'storeSection'])->middleware('permission:sections.manage');
            Route::get('/people', [AdminController::class, 'people'])->middleware('permission:people.view')->name('people');
            Route::post('/people', [AdminController::class, 'storePerson'])->middleware('permission:people.manage');
            Route::post('/people/{person}/activate', [AdminController::class, 'activateAccount'])->middleware('permission:identity.manage');
            Route::post('/roles', [AdminController::class, 'storeRole'])->middleware('permission:roles.manage');
            Route::get('/admissions', [AdminController::class, 'admissions'])->middleware('permission:admissions.configure')->name('admissions');
            Route::post('/admission-forms', [AdminController::class, 'storeForm'])->middleware('permission:admissions.configure');
            Route::post('/admission-form-versions/{version}/publish', [AdminController::class, 'publishForm'])->middleware('permission:admissions.configure');
            Route::post('/admission-cycles', [AdminController::class, 'storeCycle'])->middleware('permission:admissions.configure');
            Route::get('/matriculation', [AdminController::class, 'matriculations'])->middleware('permission:matriculation.approve')->name('matriculation');
            Route::post('/matriculation/{pending}/approve', [AdminController::class, 'approveMatriculation'])->middleware('permission:matriculation.approve');
            Route::get('/audit', [AdminController::class, 'audit'])->middleware('permission:audit.view')->name('audit');
        });
    });
});
Route::get('/payments/sandbox/{reference}', fn (string $reference) => response()->view('sandbox-payment', compact('reference')))->name('payments.sandbox');
Route::get('/transcripts/verify/{token}', [TranscriptController::class, 'verify'])->name('transcripts.verify');
