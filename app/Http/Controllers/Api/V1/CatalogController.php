<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Models\Program; use App\Models\Section; use Illuminate\Http\JsonResponse;
class CatalogController extends Controller { public function programs(): JsonResponse { return response()->json(['data'=>Program::with('department:id,code,name_en,name_ar')->where('active',true)->paginate()]); } public function sections(): JsonResponse { return response()->json(['data'=>Section::with(['courseVersion.course','academicPeriod','campus'])->paginate()]); } }
