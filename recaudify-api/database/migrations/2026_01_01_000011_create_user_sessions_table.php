<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("user_sessions", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained("users")->cascadeOnDelete();
            $table->string("session_id")->unique(); // valor del claim JWT "session_id"
            $table->string("ip_address", 45)->nullable();
            $table->text("user_agent")->nullable();
            $table->string("os_name")->nullable();
            $table->string("os_version")->nullable();
            $table->string("device_type")->nullable(); // mobile | tablet | desktop
            $table->timestamp("last_used_at")->nullable();
            $table->timestamp("expires_at");
            $table->timestamp("revoked_at")->nullable();
            $table->timestamps();

            $table->index("user_id");
            $table->index("revoked_at");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("user_sessions");
    }
};
