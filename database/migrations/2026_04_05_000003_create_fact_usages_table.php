<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fact_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fact_id')->constrained('facts')->cascadeOnDelete();
            $table->unsignedSmallInteger('usage_year');
            $table->timestamp('used_at')->useCurrent();
            $table->unique(['fact_id', 'usage_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_usages');
    }
};
