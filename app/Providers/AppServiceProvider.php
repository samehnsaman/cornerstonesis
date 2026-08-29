<?php

namespace App\Providers;

use App\Contracts\LmsConnector;
use App\Contracts\PaymentGateway;
use App\Contracts\SmsGateway;
use App\Integrations\ManualSmsGateway;
use App\Integrations\Moodle\MoodleConnector;
use App\Integrations\SandboxPaymentGateway;
use Illuminate\Support\Facades\URL;
use Laravel\Passport\Passport;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LmsConnector::class, MoodleConnector::class);
        $this->app->bind(PaymentGateway::class, SandboxPaymentGateway::class);
        $this->app->bind(SmsGateway::class, ManualSmsGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::tokensCan([
            'students:read' => 'Read students',
            'catalog:read' => 'Read catalog and sections',
            'enrollments:read' => 'Read enrollments',
            'finance:read' => 'Read student account summaries',
        ]);

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
