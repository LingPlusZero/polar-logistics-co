<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career', function (Blueprint $table) {
            // 空值代表不限部門（例如實習精靈於各部門輪調）
            $table->foreignId('department_id')->nullable()->after('title')
                ->constrained('department')->restrictOnDelete();
        });

        // 既有職缺原本存部門名稱：補建部門後回填關聯，「各部門」不是實際部門，維持空值
        $names = DB::table('career')->where('department', '!=', '各部門')->distinct()->pluck('department');

        foreach ($names as $name) {
            DB::table('department')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('career')
                ->where('department', $name)
                ->update(['department_id' => DB::table('department')->where('name', $name)->value('id')]);
        }

        Schema::table('career', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }

    public function down(): void
    {
        Schema::table('career', function (Blueprint $table) {
            $table->string('department')->default('各部門')->after('title');
        });

        DB::table('career')
            ->join('department', 'department.id', '=', 'career.department_id')
            ->update(['career.department' => DB::raw('department.name')]);

        Schema::table('career', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
