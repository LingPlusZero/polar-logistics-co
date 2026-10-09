<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_request', function (Blueprint $table) {
            // 馴鹿由照護專員代請：elf_id 仍是申請人（照護專員），這裡記錄是替哪隻馴鹿請；null＝替自己請
            $table->foreignId('reindeer_id')->nullable()->after('elf_id')->comment('代請假的馴鹿，reindeer.id；null＝精靈自己請假')
                ->constrained('reindeer')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leave_request', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reindeer_id');
        });
    }
};
