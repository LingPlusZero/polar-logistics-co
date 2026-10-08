<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// 正式環境拒絕非 HTTPS 的請求，避免密碼與權杖以明碼傳輸；開發環境（自簽憑證）也走 HTTPS，但不強制
class RequireSecureConnection
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->isProduction() && ! $request->isSecure()) {
            return response()->json(['message' => '請使用 HTTPS 連線'], 403);
        }

        return $next($request);
    }
}
