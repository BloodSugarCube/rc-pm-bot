<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_poll_sessions', function (Blueprint $table): void {
            $table->string('day_message_id')->nullable()->after('morning_message_id');
            $table->boolean('day_reminder_sent')->default(false)->after('day_poll_sent');
        });
    }

    public function down(): void
    {
        Schema::table('daily_poll_sessions', function (Blueprint $table): void {
            $table->dropColumn(['day_message_id', 'day_reminder_sent']);
        });
    }
};
