<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoAccessSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleTemplateSeeder::class);

        $campusId = DB::table('campuses')->where('code', 'MAIN')->value('id');
        $this->staff('admissions@example.test', 'Demo Admissions Officer', 'SISPOC-STAFF-0001', 'admissions-officer', $campusId);
        $this->staff('admin@example.test', 'Demo System Administrator', 'SISPOC-STAFF-ADMIN', 'system-administrator', null);

        $applicantId = User::where('email', 'applicant@example.test')->value('id');
        if ($applicantId) {
            DB::table('role_assignments')->where('user_id', $applicantId)->delete();
        }
    }

    private function staff(string $email, string $name, string $staffNumber, string $roleCode, ?string $campusId): void
    {
        $user = User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => Hash::make('DemoStaffPassword123!'), 'email_verified_at' => now(), 'locale' => 'en', 'mfa_required' => true, 'status' => 'active', 'must_change_password' => false]);
        Person::updateOrCreate(['user_id' => $user->id], ['external_id' => $staffNumber, 'staff_number' => $staffNumber, 'given_name' => 'Demo', 'family_name' => Str::after($name, 'Demo '), 'email' => $email, 'employment_status' => 'active', 'instructor_eligible' => true]);
        $role = Role::where('code', $roleCode)->firstOrFail();
        DB::table('role_assignments')->updateOrInsert(['user_id' => $user->id, 'role_id' => $role->id], ['id' => (string) Str::uuid(), 'campus_id' => $campusId, 'assigned_by' => $user->id, 'revoked_at' => null, 'created_at' => now(), 'updated_at' => now()]);
    }
}
