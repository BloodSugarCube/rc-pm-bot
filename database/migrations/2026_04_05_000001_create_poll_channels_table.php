<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poll_channels', function (Blueprint $table) {
            $table->id();
            $table->string('rocket_room_id')->unique();
            $table->string('name');
            $table->string('room_type', 8);
            $table->boolean('is_poll_active')->default(false);
            $table->timestamp('last_exported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_channels');
    }
};
