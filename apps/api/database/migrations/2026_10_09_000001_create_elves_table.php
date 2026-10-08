<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elves', function (Blueprint $table) {
            $table->comment('精靈（員工），精靈管理系統的登入帳號');
            $table->id()->comment('主鍵');
            $table->string('number', 10)->unique()->comment('精靈編號，登入帳號，例如 E001');
            $table->string('name', 50)->comment('姓名');
            // restrict：有精靈的部門不能直接刪除
            $table->foreignId('department_id')->comment('所屬部門，department.id')
                ->constrained('department')->restrictOnDelete();
            $table->string('rank', 20)->comment('職級：實習精靈／正式精靈／資深精靈／部長／副聖誕老人');
            $table->date('hired_at')->comment('到職日，年資由此計算');
            $table->string('status', 20)->default('正常')->comment('狀態：正常／請假／可能失蹤');
            $table->string('note', 500)->nullable()->comment('備註');
            $table->string('password')->comment('密碼的 bcrypt 雜湊，不存明碼');
            $table->string('api_token', 64)->nullable()->unique()->comment('登入權杖的 sha256 雜湊，不存明碼；空值＝未登入');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
            $table->timestamp('updated_at')->nullable()->comment('更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elves');
    }
};
