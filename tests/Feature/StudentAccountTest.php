<?php
namespace Tests\Feature;
use App\Models\{AcademicPeriod,Person,User}; use App\Services\StudentAccountService; use Illuminate\Foundation\Testing\RefreshDatabase; use Illuminate\Validation\ValidationException; use Tests\TestCase;
class StudentAccountTest extends TestCase { use RefreshDatabase;
 public function test_posting_must_balance():void{$this->seed();$this->actingAs(User::first());$this->expectException(ValidationException::class);app(StudentAccountService::class)->post(['person_id'=>Person::first()->id,'type'=>'charge','reference'=>'BAD-1','currency'=>'USD','description'=>'bad','effective_on'=>today()],[['account_code'=>'AR','debit'=>'10','credit'=>'0']]);}
 public function test_balanced_posting_is_append_only():void{$this->seed();$this->actingAs(User::first());$tx=app(StudentAccountService::class)->post(['person_id'=>Person::first()->id,'type'=>'charge','reference'=>'OK-1','currency'=>'USD','description'=>'Tuition','effective_on'=>today()],[['account_code'=>'AR','debit'=>'100','credit'=>'0'],['account_code'=>'TUITION','debit'=>'0','credit'=>'100']]);$this->assertCount(2,$tx->entries);$this->assertEquals('100.0000',$tx->entries->sum('debit'));}
}
