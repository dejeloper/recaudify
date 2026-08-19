<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("state_transitions", function (Blueprint $table) {
            $table->id();
            $table->string("entity");
            // Null = transición de creación: el registro nace directamente en to_state.
            $table->foreignId("from_state_id")->nullable()->constrained("states")->cascadeOnDelete();
            $table->foreignId("to_state_id")->constrained("states")->cascadeOnDelete();
            // Permiso Spatie exigido para ejecutarla a mano. Null = no exige permiso propio.
            $table->string("permission")->nullable();
            // La ejecuta el motor (job de mora), no una persona.
            $table->boolean("is_automatic")->default(false);
            // Exige aprobación previa vía el módulo de autorizaciones.
            $table->boolean("requires_authorization")->default(false);
            // Exige que quien la ejecuta escriba por qué.
            $table->boolean("requires_reason")->default(false);
            $table->string("label")->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(["entity", "from_state_id", "to_state_id", "deleted_at"], "state_transitions_unique");
            $table->index(["entity", "from_state_id"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("state_transitions");
    }
};
