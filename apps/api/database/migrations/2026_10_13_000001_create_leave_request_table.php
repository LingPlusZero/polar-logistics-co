<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_request', function (Blueprint $table) {
            $table->comment('請假單');
            $table->id()->comment('主鍵');
            // 申請人離職（刪除）後假單沒有對象，一併刪除；審核人刪除則保留假單、欄位設為空
            $table->foreignId('elf_id')->comment('請假的精靈，elves.id')
                ->constrained('elves')->cascadeOnDelete();
            $table->string('leave_type', 30)->comment('假別：普通病假／魔力枯竭假／被人類目擊後心理創傷假');
            $table->date('start_date')->comment('請假起日');
            $table->date('end_date')->comment('請假迄日（含當天），由起日與假別天數算出');
            $table->date('applied_at')->comment('申請日期');
            $table->string('status', 20)->default('審核中')->comment('審核結果：審核中／核准／駁回');
            $table->date('reviewed_at')->nullable()->comment('審核日期');
            $table->foreignId('reviewer_id')->nullable()->comment('審核人，elves.id')
                ->constrained('elves')->nullOnDelete();
            $table->string('reject_reason', 500)->nullable()->comment('駁回理由，駁回時必填');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');

            // 找某人某段期間的假單（重疊檢查、是否正在請假）
            $table->index(['elf_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_request');
    }
};
