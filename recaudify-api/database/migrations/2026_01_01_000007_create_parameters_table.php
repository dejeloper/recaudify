<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("parameters", function (Blueprint $table) {
            $table->id();
            $table->string("type");
            $table->string("key");
            $table->text("value")->nullable();
            $table->string("cast")->default("string");
            $table->string("description")->nullable();
            $table->boolean("is_editable")->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(["type", "key"]);
            $table->index("type");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("parameters");
    }
};
