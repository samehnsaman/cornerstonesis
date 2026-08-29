<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Models\Person; use Illuminate\Http\JsonResponse;
class StudentController extends Controller { public function show(Person $person): JsonResponse { return response()->json(['data'=>$person->load(['programEnrollments.program','programEnrollments.termEnrollments.registrations.section'])]); } }
