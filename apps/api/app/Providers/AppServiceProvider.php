<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 正式環境一律關閉詳細錯誤訊息（堆疊、SQL、檔案路徑），即使 .env 誤設 APP_DEBUG=true
        if ($this->app->isProduction()) {
            config(['app.debug' => false]);
        }

        // 回傳純 JSON，不額外包一層 data
        JsonResource::withoutWrapping();

        // throttleApi() 使用的 api 限流器，依來源 IP 每分鐘 60 次
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
    }
}
