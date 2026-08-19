<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("states", function (Blueprint $table) {
            $table->id();
            // Entidad dueña del ciclo de vida: client, contract, payment, management, commitment.
            $table->string("entity");
            $table->string("key");
            $table->string("name");
            $table->string("description")->nullable();
            $table->string("color")->nullable();
            $table->string("icon")->nullable();
            // El estado con el que nace un registro nuevo de esa entidad. Uno solo por entidad.
            $table->boolean("is_initial")->default(false);
            // Estado terminal: no tiene transiciones de salida (finalizado, cancelado).
            $table->boolean("is_final")->default(false);
            $table->unsignedInteger("sort_order")->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(["entity", "key", "deleted_at"]);
            $table->index(["entity", "sort_order"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("states");
    }
};
