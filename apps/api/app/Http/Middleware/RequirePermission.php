<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// 用法：RequirePermission::class.':career.manage'；須排在 AuthenticateElf 之後
class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (! $request->user()?->hasPermission($permission)) {
            return response()->json(['message' => '沒有權限執行此操作'], 403);
        }

        return $next($request);
    }
}
