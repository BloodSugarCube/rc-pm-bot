<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_poll_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_channel_id')->constrained('poll_channels')->cascadeOnDelete();
            $table->date('poll_date');
            $table->string('rocket_room_id');
            $table->string('morning_message_id');
            $table->foreignId('fact_id')->nullable()->constrained('facts')->nullOnDelete();
            $table->boolean('morning_reminder_sent')->default(false);
            $table->boolean('day_poll_sent')->default(false);
            $table->timestamps();
            $table->unique(['poll_channel_id', 'poll_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_poll_sessions');
    }
};
