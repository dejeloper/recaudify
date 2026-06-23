<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('day_of_week')->unsigned()->comment('0=Domingo, 6=Sábado');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('show_status')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_schedules');
    }
};
