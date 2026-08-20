<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("branches", function (Blueprint $table) {
            $table->id();
            $table->string("code", 20);
            $table->string("name", 100);
            $table->string("address")->nullable();
            $table->string("city", 100)->nullable();
            $table->string("phone", 30)->nullable();
            $table->string("email")->nullable();
            $table->boolean("is_main")->default(false);
            $table->unsignedInteger("sort_order")->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(["code", "deleted_at"]);
            $table->unique(["name", "deleted_at"]);
            $table->index("sort_order");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("branches");
    }
};
