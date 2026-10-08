<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_statics', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedBigInteger('gifts_delivered');
            $table->decimal('growth_rate', 5, 2);
            $table->decimal('on_time_rate', 5, 2);
            $table->decimal('complete_rate', 5, 2);
            $table->decimal('feedback_rate', 5, 2);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_statics');
    }
};
