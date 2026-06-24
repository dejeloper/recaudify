<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tableNames = config("permission.table_names");

        Schema::create($tableNames["permissions"], function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("guard_name")->default("api");
            $table->timestamps();
            $table->softDeletes();

            $table->unique(["name", "guard_name"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config("permission.table_names")["permissions"]);
    }
};
