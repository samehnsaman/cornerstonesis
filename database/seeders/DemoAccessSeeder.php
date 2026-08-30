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
        $staff = User::updateOrCreate(['email' => 'admissions@example.test'], [
            'name' => 'Demo Admissions Officer', 'password' => Hash::make('DemoStaffPassword123!'),
            'email_verified_at' => now(), 'locale' => 'en', 'mfa_required' => false,
        ]);
        Person::updateOrCreate(['user_id' => $staff->id], [
            'external_id' => 'SISPOC-STAFF-0001', 'given_name' => 'Demo', 'family_name' => 'Admissions Officer',
            'given_name_ar' => 'موظف', 'family_name_ar' => 'قبول تجريبي', 'email' => $staff->email,
        ]);
        $role = Role::updateOrCreate(['code' => 'admissions-officer'], [
            'name_en' => 'Admissions Officer', 'name_ar' => 'موظف القبول',
            'permissions' => ['applications.review', 'applications.decide'], 'privileged' => true,
        ]);
        $campusId = DB::table('campuses')->where('code', 'MAIN')->value('id');
        $assignment = DB::table('role_assignments')->where('user_id', $staff->id)->where('role_id', $role->id);
        if ($assignment->exists()) {
            $assignment->update(['campus_id' => $campusId, 'updated_at' => now()]);
        } else {
            DB::table('role_assignments')->insert(['id' => (string) Str::uuid(), 'user_id' => $staff->id, 'role_id' => $role->id, 'campus_id' => $campusId, 'created_at' => now(), 'updated_at' => now()]);
        }

        $applicantId = User::where('email', 'applicant@example.test')->value('id');
        if ($applicantId) {
            DB::table('role_assignments')->where('user_id', $applicantId)->delete();
        }
    }
}
