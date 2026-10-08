<?php

namespace App\Http\Middleware;

use App\Models\Elf;
use Closure;
use Illuminate\Http\Request;

// 以 Bearer 權杖辨識精靈；純 API 不使用 cookie，因此不受 CSRF 影響
class AuthenticateElf
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        $elf = $token ? Elf::where('api_token', hash('sha256', $token))->first() : null;

        if (! $elf) {
            return response()->json(['message' => '尚未登入或登入已失效'], 401);
        }

        $request->setUserResolver(fn () => $elf);

        return $next($request);
    }
}
