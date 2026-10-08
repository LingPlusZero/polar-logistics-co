<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // 部門資料表（department）尚未建立，先以文字儲存部門名稱
            $table->string('department');
            $table->text('description');
            $table->text('requirements');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career');
    }
};
