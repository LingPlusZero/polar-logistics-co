<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career', function (Blueprint $table) {
            // 福利、轉正機會並非每個職缺都有，故為選填
            $table->text('benefits')->nullable()->after('requirements');
            $table->text('promotion')->nullable()->after('benefits');
        });
    }

    public function down(): void
    {
        Schema::table('career', function (Blueprint $table) {
            $table->dropColumn(['benefits', 'promotion']);
        });
    }
};
