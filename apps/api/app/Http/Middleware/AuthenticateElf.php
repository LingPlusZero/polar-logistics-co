<?php

namespace App\Http\Middleware;

use App\Models\Elf;
use Closure;
use Illuminate\Http\Request;

// 以 Bearer 權杖辨識精靈；純 API 不使用 cookie，因此不受 CSRF 影響
class AuthenticateElf
{
    // 「最後使用時間」至少隔這麼久才寫入一次，避免每個請求都寫資料庫
    private const TOUCH_INTERVAL_SECONDS = 60;

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        $elf = $token ? Elf::where('api_token', hash('sha256', $token))->first() : null;

        if (! $elf) {
            return response()->json(['message' => '尚未登入或登入已失效'], 401);
        }

        // 閒置太久就讓權杖失效；回傳 reason 讓前端能提示「閒置自動登出」。
        // 空值（舊權杖還沒有紀錄）視為剛使用過
        $lastUsed = $elf->api_token_used_at;

        if ($lastUsed && $lastUsed->diffInMinutes(now(), true) >= Elf::IDLE_TIMEOUT_MINUTES) {
            $elf->forceFill(['api_token' => null, 'api_token_used_at' => null])->save();

            return response()->json([
                'message' => '閒置超過 '.Elf::IDLE_TIMEOUT_MINUTES.' 分鐘，已自動登出',
                'reason' => 'idle',
            ], 401);
        }

        if (! $lastUsed || $lastUsed->diffInSeconds(now(), true) >= self::TOUCH_INTERVAL_SECONDS) {
            $elf->forceFill(['api_token_used_at' => now()])->save();
        }

        $request->setUserResolver(fn () => $elf);

        return $next($request);
    }
}
