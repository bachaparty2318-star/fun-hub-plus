<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $removedTables = [
        'email_verifications', 'password_resets', 'user_sessions',
        'usage_statistics', 'chatbot_queries', 'chatbot_faq',
    ];

    public function up(): void
    {
        // Never discard records when applying this simplification to another installation.
        foreach ($this->removedTables as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("Cannot simplify: {$table} contains data. Migrate or archive it first.");
            }
        }

        DB::table('users')
            ->where('is_email_verified', 1)
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => DB::raw('created_at')]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_email_verified');
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            // Small editable preference list; category interests retain their existing pivot.
            $table->json('favorite_fandoms')->nullable();
        });

        foreach (['content', 'articles', 'merchandise_items'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('fandom_name', 150)->nullable()->index();
            });
        }

        foreach (['articles', 'character_profiles'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('genre', 100)->nullable();
                $table->date('release_date')->nullable()->index();
                $table->unsignedInteger('popularity_score')->default(0)->index();
            });
        }

        // A release listing need not pretend to be an article or a playable video.
        DB::statement("ALTER TABLE content MODIFY type ENUM('article','video','audio','image','trailer','animated_explainer','release') NOT NULL");

        foreach ($this->removedTables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Narrowing the enum or removing populated fields would otherwise lose data.
        if (DB::table('content')->whereIn('type', ['animated_explainer', 'release'])->exists()) {
            throw new RuntimeException('Cannot roll back while new content types are in use.');
        }
        if (DB::table('user_profiles')->whereNotNull('favorite_fandoms')->exists()) {
            throw new RuntimeException('Cannot roll back while favorite fandom preferences are in use.');
        }
        foreach (['content', 'articles', 'merchandise_items'] as $name) {
            if (DB::table($name)->whereNotNull('fandom_name')->exists()) {
                throw new RuntimeException("Cannot roll back populated fandom names in {$name}.");
            }
        }
        foreach (['articles', 'character_profiles'] as $name) {
            if (DB::table($name)->whereNotNull('genre')->orWhereNotNull('release_date')->orWhere('popularity_score', '<>', 0)->exists()) {
                throw new RuntimeException("Cannot roll back populated explorer fields in {$name}.");
            }
        }

        foreach ([
            '2026_09_24_000002_create_email_verifications_table.php',
            '2026_09_24_000003_create_password_resets_table.php',
            '2026_09_24_000004_create_user_sessions_table.php',
            '2026_09_24_000021_create_chatbot_faq_table.php',
            '2026_09_24_000022_create_chatbot_queries_table.php',
            '2026_09_24_000025_create_usage_statistics_table.php',
        ] as $file) {
            (require __DIR__.'/'.$file)->up();
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_email_verified')->default(false);
        });
        DB::table('users')->whereNotNull('email_verified_at')->update(['is_email_verified' => 1]);

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('favorite_fandoms');
        });
        foreach (['content', 'articles', 'merchandise_items'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['fandom_name']);
                $table->dropColumn('fandom_name');
            });
        }
        foreach (['articles', 'character_profiles'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['release_date']);
                $table->dropIndex(['popularity_score']);
                $table->dropColumn(['genre', 'release_date', 'popularity_score']);
            });
        }
        DB::statement("ALTER TABLE content MODIFY type ENUM('article','video','audio','image','trailer') NOT NULL");
    }
};