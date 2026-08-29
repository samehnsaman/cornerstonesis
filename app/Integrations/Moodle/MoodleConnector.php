<?php

namespace App\Integrations\Moodle;

use App\Contracts\LmsConnector;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MoodleConnector implements LmsConnector
{
    public function discover(): array
    {
        return $this->call('core_webservice_get_site_info');
    }

    public function handle(string $event, array $payload): array
    {
        return match ($event) {
            'registration.enrolled' => $this->call('enrol_manual_enrol_users', ['enrolments' => [[
                'roleid' => (int) config('integrations.moodle.student_role_id', 5),
                'userid' => (int) $payload['moodle_user_id'],
                'courseid' => (int) $payload['moodle_course_id'],
            ]]]),
            'registration.suspended' => $this->call('core_enrol_edit_user_enrolment', [
                'courseid' => (int) $payload['moodle_course_id'],
                'userid' => (int) $payload['moodle_user_id'],
                'status' => 1,
            ]),
            'grades.fetch' => $this->call('gradereport_user_get_grade_items', [
                'courseid' => (int) $payload['moodle_course_id'],
                'userid' => (int) $payload['moodle_user_id'],
            ]),
            default => throw new RuntimeException("Unsupported Moodle event: {$event}"),
        };
    }

    public function loginUrl(string $username): string
    {
        $response = $this->call('auth_userkey_request_login_url', ['user' => ['username' => $username]]);
        $url = $response['loginurl'] ?? null;
        throw_unless(is_string($url) && str_starts_with($url, config('integrations.moodle.base_url')), RuntimeException::class, 'Moodle returned an invalid login URL.');

        return $url;
    }

    private function call(string $function, array $parameters = []): array
    {
        $response = $this->client()->post('/webservice/rest/server.php', [
            'wstoken' => config('integrations.moodle.token'),
            'wsfunction' => $function,
            'moodlewsrestformat' => 'json',
            ...$parameters,
        ])->throw()->json();

        if (isset($response['exception'])) {
            throw new RuntimeException('Moodle API error: '.($response['message'] ?? $response['exception']));
        }

        return is_array($response) ? $response : [];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('integrations.moodle.base_url'), '/'))
            ->acceptJson()->asForm()->timeout(15)->retry(2, 500, throw: false);
    }
}
