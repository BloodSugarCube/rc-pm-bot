<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mention_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mention_tag');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mention_group_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mention_group_id')->constrained('mention_groups')->cascadeOnDelete();
            $table->string('rocket_username', 191);
            $table->unique(['mention_group_id', 'rocket_username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mention_group_users');
        Schema::dropIfExists('mention_groups');
    }
};
