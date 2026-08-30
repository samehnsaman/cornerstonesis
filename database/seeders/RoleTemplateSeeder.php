<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'system-administrator' => ['System Administrator', 'مدير النظام', ['*'], true],
            'identity-administrator' => ['Identity Administrator', 'مدير الهوية', ['admin.access', 'people.view', 'people.manage', 'identity.manage', 'roles.manage', 'audit.view'], true],
            'academic-administrator' => ['Academic Administrator', 'المدير الأكاديمي', ['admin.access', 'college.manage', 'catalog.view', 'catalog.manage', 'catalog.publish', 'sections.manage', 'people.view'], true],
            'admissions-officer' => ['Admissions Officer', 'موظف القبول', ['admin.access', 'admissions.configure', 'applications.review', 'applications.decide'], true],
            'registrar' => ['Registrar', 'المسجل', ['admin.access', 'catalog.view', 'sections.manage', 'applications.decide', 'matriculation.approve', 'audit.view'], true],
            'department-administrator' => ['Department Administrator', 'مدير القسم', ['admin.access', 'catalog.view', 'catalog.manage', 'sections.manage', 'people.view'], true],
            'read-only-auditor' => ['Read-only Auditor', 'مدقق للقراءة فقط', ['admin.access', 'catalog.view', 'people.view', 'audit.view'], true],
        ];

        foreach ($templates as $code => [$nameEn, $nameAr, $permissions, $privileged]) {
            Role::updateOrCreate(
                ['code' => $code],
                ['name_en' => $nameEn, 'name_ar' => $nameAr, 'permissions' => $permissions, 'privileged' => $privileged],
            );
        }
    }
}
