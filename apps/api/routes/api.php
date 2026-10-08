<?php

use App\Http\Controllers\AnnualStaticController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\DepartmentController;
use App\Http\Middleware\AuthenticateElf;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\RequireSecureConnection;
use Illuminate\Support\Facades\Route;

// 所有 API 皆掛在 /api 底下（Laravel 預設前綴），清單與規格見 docs/api.md
Route::get('/ping', fn () => response()->json(['status' => 'ok']));

// 職缺：讀取公開（官網使用），寫入需登入且有職缺管理權限（人力資源部）
Route::get('/career', [CareerController::class, 'index']);
Route::middleware([AuthenticateElf::class, RequireSecureConnection::class, RequirePermission::class.':career.manage'])
    ->group(function () {
        Route::post('/career', [CareerController::class, 'store']);
        Route::put('/career/{career}', [CareerController::class, 'update']);
        Route::delete('/career/{career}', [CareerController::class, 'destroy']);
    });
Route::get('/statics/annual', [AnnualStaticController::class, 'index']);
Route::get('/department', [DepartmentController::class, 'index']);

// 登入較嚴格限流（每個 IP 每分鐘 10 次），避免被拿來窮舉精靈編號；正式環境限 HTTPS
Route::post('/auth/login', [AuthController::class, 'login'])->middleware(['throttle:10,1', RequireSecureConnection::class]);

Route::middleware([AuthenticateElf::class, RequireSecureConnection::class])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});
