<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint', function (Blueprint $table) {
            $table->comment('精靈被申訴紀錄');
            $table->id()->comment('主鍵');
            // 被申訴人刪除後紀錄也沒有對象，一併刪除；申訴人與處理人刪除則保留紀錄、欄位設為空
            $table->foreignId('elf_id')->comment('被申訴人，elves.id')
                ->constrained('elves')->cascadeOnDelete();
            $table->foreignId('complainant_id')->nullable()->comment('申訴人，elves.id')
                ->constrained('elves')->nullOnDelete();
            $table->string('reason', 500)->comment('申訴事由');
            $table->date('filed_at')->comment('申訴日期');
            $table->string('status', 20)->default('處理中')->comment('狀態：處理中／已結案');
            $table->text('resolution')->nullable()->comment('後續處理說明，結案時必填');
            $table->foreignId('handler_id')->nullable()->comment('處理人（結案者），elves.id')
                ->constrained('elves')->nullOnDelete();
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint');
    }
};
