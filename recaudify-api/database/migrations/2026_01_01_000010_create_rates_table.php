<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("rates", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->foreignId("product_id")->constrained("products");
            $table->unsignedBigInteger("value"); // valor total (entero)
            $table->unsignedInteger("installments"); // número de cuotas
            $table->unsignedBigInteger("installment_value"); // valor de cada cuota
            $table->unsignedBigInteger("discount")->default(0);
            $table->boolean("active")->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("rates");
    }
};
