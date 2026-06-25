<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("states", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            // Agrupador de estado como string directo (legacy: TiposEstados):
            // user | client | seller | contract | scheduled_payment | collector
            $table->string("state_type");
            $table->boolean("active")->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index("state_type");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("states");
    }
};
