<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tableNames = config("permission.table_names");
        $columnNames = config("permission.column_names");
        $teams = config("permission.teams");

        Schema::create($tableNames["roles"], function (Blueprint $table) use ($teams, $columnNames) {
            $table->id();
            if ($teams || config("permission.testing")) {
                $table->unsignedBigInteger($columnNames["team_foreign_key"])->nullable();
                $table->index($columnNames["team_foreign_key"], "roles_team_foreign_key_index");
            }
            $table->string("name");
            $table->string("guard_name");
            $table->timestamps();
            $table->softDeletes();

            if ($teams || config("permission.testing")) {
                $table->unique([$columnNames["team_foreign_key"], "name", "guard_name", "deleted_at"]);
            } else {
                $table->unique(["name", "guard_name", "deleted_at"]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config("permission.table_names")["roles"]);
    }
};
