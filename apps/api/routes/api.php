<?php

use App\Http\Controllers\AnnualStaticController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\DepartmentController;
use Illuminate\Support\Facades\Route;

// 所有 API 皆掛在 /api 底下（Laravel 預設前綴），清單與規格見 docs/api.md
Route::get('/ping', fn () => response()->json(['status' => 'ok']));

Route::apiResource('career', CareerController::class)->except('show');
Route::get('/statics/annual', [AnnualStaticController::class, 'index']);
Route::get('/department', [DepartmentController::class, 'index']);
