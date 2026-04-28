<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_absence_periods', function (Blueprint $table): void {
            $table->id();
            $table->date('date_from');
            $table->date('date_to');
            $table->string('employee_tag', 191);
            $table->string('username_normalized', 191);
            $table->timestamps();

            $table->index(['date_from', 'date_to']);
            $table->index('username_normalized');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_absence_periods');
    }
};
