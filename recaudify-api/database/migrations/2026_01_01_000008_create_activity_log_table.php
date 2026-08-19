<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("activity_log", function (Blueprint $table) {
            $table->id();
            $table->string("log_name")->nullable()->index();
            $table->text("description");
            $table->nullableMorphs("subject", "subject");
            $table->string("event")->nullable();
            $table->nullableMorphs("causer", "causer");
            $table->string("causer_username")->nullable();
            $table->string("causer_name")->nullable();
            $table->json("properties")->nullable();
            $table->uuid("batch_uuid")->nullable();
            $table->timestamps();
            $table->index("created_at");
        });
    }
};
