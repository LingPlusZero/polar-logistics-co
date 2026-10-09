<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('elves', function (Blueprint $table) {
            $table->timestamp('api_token_used_at')->nullable()->after('api_token')
                ->comment('登入權杖最後使用時間，閒置超過 30 分鐘視為失效');
        });
    }

    public function down(): void
    {
        Schema::table('elves', function (Blueprint $table) {
            $table->dropColumn('api_token_used_at');
        });
    }
};
