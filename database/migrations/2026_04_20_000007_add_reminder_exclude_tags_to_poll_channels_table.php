<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poll_channels', function (Blueprint $table): void {
            $table->text('reminder_exclude_tags')->nullable()->after('team_tags');
        });
    }

    public function down(): void
    {
        Schema::table('poll_channels', function (Blueprint $table): void {
            $table->dropColumn('reminder_exclude_tags');
        });
    }
};
