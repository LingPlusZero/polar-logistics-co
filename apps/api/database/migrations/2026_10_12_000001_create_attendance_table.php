<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->comment('精靈出勤紀錄（沒有真正的打卡機制，資料由 seeder 產生）');
            $table->id()->comment('主鍵');
            // 精靈刪除後出勤紀錄也沒有對象，一併刪除
            $table->foreignId('elf_id')->comment('精靈，elves.id')
                ->constrained('elves')->cascadeOnDelete();
            $table->dateTime('clock_in')->comment('上班時間');
            $table->dateTime('clock_out')->comment('下班時間；工作時數由上下班時間計算，不存欄位');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');

            $table->index('clock_in');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
