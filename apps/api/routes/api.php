<?php

use Illuminate\Support\Facades\Route;

// 所有 API 皆掛在 /api 底下（Laravel 預設前綴），清單與規格見 docs/api.md
Route::get('/ping', fn () => response()->json(['status' => 'ok']));
