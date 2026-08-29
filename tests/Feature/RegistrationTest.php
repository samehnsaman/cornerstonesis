<?php
namespace Tests\Feature;
use App\Models\{Section,TermEnrollment,User}; use App\Services\RegistrationService; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class RegistrationTest extends TestCase { use RefreshDatabase;
 public function test_student_can_register_only_once():void{$this->seed();$this->actingAs(User::first());$service=app(RegistrationService::class);$first=$service->register(TermEnrollment::first(),Section::first());$second=$service->register(TermEnrollment::first(),Section::first());$this->assertDatabaseCount('registrations',1);$this->assertTrue($first->is($second));}
 public function test_capacity_places_next_student_on_waitlist():void{$this->seed();$this->actingAs(User::first());$section=Section::first();$section->update(['capacity'=>0]);$registration=app(RegistrationService::class)->register(TermEnrollment::first(),$section);$this->assertSame('waitlisted',$registration->status);}
}
