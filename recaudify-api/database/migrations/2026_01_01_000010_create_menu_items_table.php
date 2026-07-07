<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("menu_items", function (Blueprint $table) {
            $table->id();
            $table->foreignId("parent_id")->nullable()->constrained("menu_items")->nullOnDelete();
            $table->string("label");
            $table->json("icons")->nullable();
            $table->string("route")->nullable();
            $table->string("permission")->nullable();
            $table->unsignedInteger("order")->default(0);
            $table->boolean("is_active")->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(["parent_id", "order"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("menu_items");
    }
};
