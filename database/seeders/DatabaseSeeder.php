<?php

namespace Database\Seeders;

use App\Models\{AcademicPeriod, Course, CourseVersion, CurriculumVersion, Department, Person, Program, ProgramEnrollment, Section, TermEnrollment, User};
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $org=Str::uuid(); $campus=Str::uuid();
        DB::table('organizations')->insert(['id'=>$org,'code'=>'CS','name_en'=>'Cornerstone Demonstration College','name_ar'=>'كلية كورنرستون التجريبية','timezone'=>'Asia/Beirut','is_poc'=>true,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('campuses')->insert(['id'=>$campus,'organization_id'=>$org,'code'=>'MAIN','name_en'=>'Main Campus','name_ar'=>'الحرم الرئيسي','timezone'=>'Asia/Beirut','active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $department=Department::create(['organization_id'=>$org,'campus_id'=>$campus,'code'=>'CSCI','name_en'=>'Computer Science','name_ar'=>'علوم الحاسوب','active'=>true]);
        $program=Program::create(['department_id'=>$department->id,'code'=>'BSCS','name_en'=>'BSc Computer Science','name_ar'=>'بكالوريوس علوم الحاسوب','award_type'=>'bachelor','required_credits'=>'120.00','active'=>true]);
        $curriculum=CurriculumVersion::create(['program_id'=>$program->id,'version'=>'2026','effective_from'=>'2026-01-01','minimum_gpa'=>'2.000','status'=>'active']);
        $period=AcademicPeriod::create(['organization_id'=>$org,'code'=>'2026-FA','name_en'=>'Fall 2026','name_ar'=>'خريف 2026','type'=>'semester','starts_on'=>'2026-09-01','ends_on'=>'2026-12-20','registration_opens_at'=>now()->subMonth(),'registration_closes_at'=>now()->addMonth(),'status'=>'open']);
        $course=Course::create(['department_id'=>$department->id,'code'=>'CSCI101','title_en'=>'Introduction to Computing','title_ar'=>'مقدمة في الحوسبة','active'=>true]);
        $version=CourseVersion::create(['course_id'=>$course->id,'version'=>'2026','effective_from'=>'2026-01-01','credit_hours'=>'3.00']);
        Section::create(['course_version_id'=>$version->id,'academic_period_id'=>$period->id,'campus_id'=>$campus,'code'=>'SISPOC-CSCI101-01','capacity'=>30,'waitlist_capacity'=>5,'status'=>'open','moodle_idnumber'=>'SISPOC-'.$version->id]);
        $user=User::create(['name'=>'Demo Applicant','email'=>'applicant@example.test','password'=>Hash::make('DemoPassword123!'),'email_verified_at'=>now(),'locale'=>'en']);
        $person=Person::create(['user_id'=>$user->id,'external_id'=>'SISPOC-STU-0001','given_name'=>'Demo','family_name'=>'Applicant','given_name_ar'=>'طالب','family_name_ar'=>'تجريبي','email'=>$user->email]);
        $enrollment=ProgramEnrollment::create(['person_id'=>$person->id,'program_id'=>$program->id,'curriculum_version_id'=>$curriculum->id,'campus_id'=>$campus,'student_number'=>'SISPOC-0001','started_on'=>'2026-09-01','status'=>'active']);
        TermEnrollment::create(['program_enrollment_id'=>$enrollment->id,'academic_period_id'=>$period->id,'status'=>'eligible','credit_limit'=>18]);
        $this->call(DemoAccessSeeder::class);
    }
}
