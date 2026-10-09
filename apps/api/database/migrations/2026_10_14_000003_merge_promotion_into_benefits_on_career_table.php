<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 轉正機會算福利的一部分，不再獨立成欄位：把既有內容接在福利後面（一行一項），再移除 promotion 欄位
return new class extends Migration
{
    public function up(): void
    {
        DB::table('career')->whereNotNull('promotion')->orderBy('id')->each(function ($career) {
            DB::table('career')->where('id', $career->id)->update([
                'benefits' => $career->benefits ? $career->benefits."\n".$career->promotion : $career->promotion,
            ]);
        });

        Schema::table('career', function (Blueprint $table) {
            $table->dropColumn('promotion');
        });
    }

    // 還原只補回欄位，已併入福利的內容不會拆回來
    public function down(): void
    {
        Schema::table('career', function (Blueprint $table) {
            $table->text('promotion')->nullable()->after('benefits')->comment('轉正機會，一行一項');
        });
    }
};
