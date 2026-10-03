<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poll_channels', function (Blueprint $table): void {
            $table->json('poll_days')->nullable()->after('reminder_exclude_tags');
        });
    }

    public function down(): void
    {
        Schema::table('poll_channels', function (Blueprint $table): void {
            $table->dropColumn('poll_days');
        });
    }
};
