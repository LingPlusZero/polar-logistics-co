<?php

namespace Tests\Feature;

use App\Models\Elf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

// 錯誤回應不可洩漏內部細節（類別名稱、檔案路徑、SQL、堆疊）
class ErrorResponseTest extends TestCase
{
    use RefreshDatabase;

    private function assertNoLeak($response): void
    {
        $body = $response->getContent();

        foreach (['App\\\\Models', 'App\\Models', 'vendor/', '/var/www', 'Illuminate', 'trace', 'exception', 'secret-detail'] as $needle) {
            $this->assertStringNotContainsString($needle, $body, "回應不應包含 {$needle}");
        }
    }

    public function test_找不到資料只回通用訊息_不帶出model類別(): void
    {
        $response = $this->getJson('/api/not-exists');

        $response->assertNotFound()->assertExactJson(['message' => '找不到資料']);
        $this->assertNoLeak($response);
    }

    public function test_路由綁定找不到model也只回通用訊息(): void
    {
        // 模擬 model 綁定失敗（與 /api/elf/9999 相同的例外類型）
        Route::get('/api/_model-missing', fn () => Elf::findOrFail(9999));

        $response = $this->getJson('/api/_model-missing');

        $response->assertNotFound()->assertExactJson(['message' => '找不到資料']);
        $this->assertNoLeak($response);
    }

    public function test_不支援的請求方法只回通用訊息(): void
    {
        $response = $this->postJson('/api/ping');

        $response->assertStatus(405)->assertExactJson(['message' => '不支援的請求方法']);
        $this->assertNoLeak($response);
    }

    public function test_未預期的錯誤在關閉除錯時只回Server_Error(): void
    {
        config(['app.debug' => false]);
        Route::get('/api/_boom', fn () => throw new \RuntimeException('secret-detail'));

        $response = $this->getJson('/api/_boom');

        $response->assertStatus(500)->assertExactJson(['message' => 'Server Error']);
        $this->assertNoLeak($response);
    }

    public function test_資料庫錯誤不會洩漏SQL(): void
    {
        config(['app.debug' => false]);
        Route::get('/api/_sql', fn () => \DB::select('select * from table_not_exists'));

        $response = $this->getJson('/api/_sql');

        $response->assertStatus(500)->assertExactJson(['message' => 'Server Error']);
        $this->assertStringNotContainsString('table_not_exists', $response->getContent());
    }

    public function test_正式環境即使誤開除錯也會強制關閉(): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => true]);

        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $this->assertFalse(config('app.debug'));
    }

    public function test_預設設定檔不開除錯(): void
    {
        $example = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('APP_DEBUG=false', $example);
    }
}
