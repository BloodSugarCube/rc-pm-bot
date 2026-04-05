<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poll_channels', function (Blueprint $table) {
            $table->text('team_tags')->nullable()->after('is_poll_active');
        });

        if (Schema::hasTable('mention_groups')) {
            $rows = DB::table('mention_groups')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('mention_tag');
            $parts = [];
            foreach ($rows as $tag) {
                $t = trim((string) $tag);
                if ($t === '') {
                    continue;
                }
                $parts[] = '@'.ltrim($t, '@');
            }
            $parts = array_values(array_unique($parts));
            if ($parts !== []) {
                DB::table('poll_channels')->update(['team_tags' => implode(', ', $parts)]);
            }
        }

        Schema::create('poll_schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->date('exception_date');
            $table->boolean('send_polls')->default(false);
            $table->timestamps();
            $table->unique('exception_date');
        });

        Schema::dropIfExists('mention_group_users');
        Schema::dropIfExists('mention_groups');
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_schedule_exceptions');

        Schema::table('poll_channels', function (Blueprint $table) {
            $table->dropColumn('team_tags');
        });

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
};
