<?php

use App\Http\Controllers\AnnualStaticController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ElfController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ReindeerController;
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

// 精靈名冊：全部需登入且有名冊權限（人力資源部）
Route::middleware([AuthenticateElf::class, RequireSecureConnection::class, RequirePermission::class.':elf.roster'])
    ->group(function () {
        Route::get('/elf', [ElfController::class, 'index']);
        Route::post('/elf', [ElfController::class, 'store']);
        Route::put('/elf/{elf}', [ElfController::class, 'update']);
        Route::delete('/elf/{elf}', [ElfController::class, 'destroy']);
    });

// 精靈被申訴紀錄：只有人力資源部能看與結案；申訴（我要申訴）所有登入者都可以，限流避免被洗版
Route::middleware([AuthenticateElf::class, RequireSecureConnection::class])->group(function () {
    Route::post('/complaint', [ComplaintController::class, 'store'])
        ->middleware([RequirePermission::class.':complaint.file', 'throttle:10,1']);
    Route::middleware(RequirePermission::class.':elf.complaint')->group(function () {
        Route::get('/complaint', [ComplaintController::class, 'index']);
        Route::post('/complaint/{complaint}/close', [ComplaintController::class, 'close']);
    });
});

// 請假：申請與查看自己的假單所有人都可以（申請限流）；審核需部長以上，且只能審核範圍內的假單
Route::middleware([AuthenticateElf::class, RequireSecureConnection::class])->group(function () {
    Route::middleware(RequirePermission::class.':leave.apply')->group(function () {
        Route::get('/leave/mine', [LeaveController::class, 'mine']);
        // 登入者擔任照護專員的馴鹿（請假申請可選擇替誰請假）
        Route::get('/reindeer/mine', [ReindeerController::class, 'mine']);
        Route::post('/leave', [LeaveController::class, 'store'])->middleware('throttle:10,1');
    });
    Route::middleware(RequirePermission::class.':leave.review')->group(function () {
        Route::get('/leave/review', [LeaveController::class, 'review']);
        Route::post('/leave/{leave}/approve', [LeaveController::class, 'approve']);
        Route::post('/leave/{leave}/reject', [LeaveController::class, 'reject']);
    });
});

// 動力單位（馴鹿）管理：人力資源部、馴鹿管理部
Route::middleware([AuthenticateElf::class, RequireSecureConnection::class, RequirePermission::class.':reindeer.manage'])
    ->group(function () {
        Route::get('/reindeer', [ReindeerController::class, 'index']);
        Route::post('/reindeer', [ReindeerController::class, 'store']);
        Route::get('/reindeer/caretakers', [ReindeerController::class, 'caretakers']);
        Route::get('/reindeer/{reindeer}/leave', [ReindeerController::class, 'leaves']);
        Route::put('/reindeer/{reindeer}', [ReindeerController::class, 'update']);
        Route::delete('/reindeer/{reindeer}', [ReindeerController::class, 'destroy']);
    });

// 精靈請假紀錄：全部精靈的假單，需人力資源部權限
Route::middleware([AuthenticateElf::class, RequireSecureConnection::class, RequirePermission::class.':elf.leave'])
    ->get('/leave/records', [LeaveController::class, 'records']);

// 精靈出勤紀錄：只有列表，需人力資源部權限
Route::middleware([AuthenticateElf::class, RequireSecureConnection::class, RequirePermission::class.':elf.attendance'])
    ->get('/attendance', [AttendanceController::class, 'index']);
Route::get('/statics/annual', [AnnualStaticController::class, 'index']);
Route::get('/department', [DepartmentController::class, 'index']);

// 登入較嚴格限流（每個 IP 每分鐘 10 次），避免被拿來窮舉精靈編號；正式環境限 HTTPS
Route::post('/auth/login', [AuthController::class, 'login'])->middleware(['throttle:10,1', RequireSecureConnection::class]);

Route::middleware([AuthenticateElf::class, RequireSecureConnection::class])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    // 比對舊密碼，同樣限流避免被拿來猜密碼
    Route::put('/auth/password', [AuthController::class, 'changePassword'])->middleware('throttle:10,1');
});
