<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reindeer', function (Blueprint $table) {
            $table->comment('動力單位（馴鹿）');
            $table->id()->comment('主鍵');
            $table->string('number', 10)->unique()->comment('動力單位編號，兩位數以上，例如 01');
            $table->string('name', 50)->comment('姓名');
            $table->date('hired_at')->comment('到職日，年資由此計算，不存欄位');
            $table->date('last_maintained_at')->comment('上次保養日期，下次保養日期（間隔 3 個月）由此計算，不存欄位');
            // 照護專員離職（刪除）後保留馴鹿、欄位設為空，等人力重新指派
            $table->foreignId('caretaker_id')->nullable()->comment('照護專員，elves.id，須為馴鹿管理部的精靈')
                ->constrained('elves')->nullOnDelete();
            $table->string('note', 500)->nullable()->comment('備註');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reindeer');
    }
};
