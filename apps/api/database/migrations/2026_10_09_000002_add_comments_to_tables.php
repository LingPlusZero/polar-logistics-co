<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 補上既有資料表與欄位的註解（MySQL）。change() 需重述完整欄位定義，改欄位型別或 nullable 時要一起更新這裡
return new class extends Migration
{
    public function up(): void
    {
        $this->apply(withComments: true);
    }

    public function down(): void
    {
        $this->apply(withComments: false);
    }

    private function apply(bool $withComments): void
    {
        // 不加註解時傳空字串，等於移除註解
        $c = fn (string $text) => $withComments ? $text : '';

        Schema::table('department', function (Blueprint $table) use ($c) {
            $table->comment($c('部門'));
            $table->unsignedBigInteger('id')->autoIncrement()->comment($c('主鍵'))->change();
            $table->string('name')->comment($c('部門名稱，唯一'))->change();
            $table->string('duty')->nullable()->comment($c('部門的工作內容，來源 docs/brand.md；董事會為空'))->change();
            $table->timestamp('created_at')->nullable()->comment($c('建立時間'))->change();
            $table->timestamp('updated_at')->nullable()->comment($c('更新時間'))->change();
        });

        Schema::table('career', function (Blueprint $table) use ($c) {
            $table->comment($c('職缺，官網人才招募頁顯示'));
            $table->unsignedBigInteger('id')->autoIncrement()->comment($c('主鍵'))->change();
            $table->string('title')->comment($c('職缺名稱'))->change();
            $table->unsignedBigInteger('department_id')->nullable()->comment($c('職缺部門，department.id；空值＝不限部門'))->change();
            $table->text('description')->comment($c('工作內容，一行一項'))->change();
            $table->text('requirements')->comment($c('任職資格，一行一項'))->change();
            $table->text('benefits')->nullable()->comment($c('福利'))->change();
            $table->text('promotion')->nullable()->comment($c('轉正機會，一行一項'))->change();
            $table->text('note')->nullable()->comment($c('備註'))->change();
            $table->timestamp('created_at')->nullable()->comment($c('建立時間'))->change();
            $table->timestamp('updated_at')->nullable()->comment($c('更新時間'))->change();
        });

        Schema::table('annual_statics', function (Blueprint $table) use ($c) {
            $table->comment($c('年度統計，官網投資人關係頁的圖表資料'));
            $table->unsignedBigInteger('id')->autoIncrement()->comment($c('主鍵'))->change();
            $table->unsignedSmallInteger('year')->comment($c('年度，唯一'))->change();
            $table->unsignedBigInteger('gifts_delivered')->comment($c('送達禮物數（份）'))->change();
            $table->decimal('growth_rate', 5, 2)->comment($c('成長率（%）'))->change();
            $table->decimal('on_time_rate', 5, 2)->comment($c('準時率（%）'))->change();
            $table->decimal('complete_rate', 5, 2)->comment($c('完整率（%）'))->change();
            $table->decimal('feedback_rate', 5, 2)->comment($c('回饋率（%）'))->change();
            $table->string('note')->nullable()->comment($c('備註'))->change();
            $table->timestamp('created_at')->nullable()->comment($c('建立時間'))->change();
            $table->timestamp('updated_at')->nullable()->comment($c('更新時間'))->change();
        });
    }
};
