<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poll_channels', function (Blueprint $table): void {
            $table->boolean('is_day_poll_active')->default(true)->after('is_poll_active');
        });

        DB::table('poll_channels')->update([
            'is_day_poll_active' => DB::raw('is_poll_active'),
        ]);
    }

    public function down(): void
    {
        Schema::table('poll_channels', function (Blueprint $table): void {
            $table->dropColumn('is_day_poll_active');
        });
    }
};
