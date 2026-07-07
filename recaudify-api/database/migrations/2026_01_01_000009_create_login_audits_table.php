<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("login_audits", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->nullable()->constrained("users")->nullOnDelete();
            $table->string("username"); // usuario intentado (útil en fallidos sin user_id)
            $table->string("status"); // success | failed
            $table->string("reason")->nullable(); // invalid_credentials | inactive | out_of_schedule
            $table->string("ip_address", 45)->nullable();
            $table->text("user_agent")->nullable();
            $table->string("os_name")->nullable();
            $table->string("os_version")->nullable();
            $table->string("device_type")->nullable(); // mobile | tablet | desktop
            $table->decimal("latitude", 10, 7)->nullable();
            $table->decimal("longitude", 10, 7)->nullable();
            $table->float("accuracy")->nullable();
            $table->timestamp("logged_at");
            $table->timestamps();

            $table->index("user_id");
            $table->index("status");
            $table->index("logged_at");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("login_audits");
    }
};
