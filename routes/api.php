<?php
use App\Http\Controllers\Api\V1\CatalogController; use App\Http\Controllers\Api\V1\StudentController; use Illuminate\Support\Facades\Route;
Route::prefix('v1')->middleware('client')->group(function (): void { Route::get('/programs',[CatalogController::class,'programs'])->middleware('scopes:catalog:read'); Route::get('/sections',[CatalogController::class,'sections'])->middleware('scopes:catalog:read'); Route::get('/students/{person}',[StudentController::class,'show'])->middleware('scopes:students:read'); });
