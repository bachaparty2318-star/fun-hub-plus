<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fan_submissions', function (Blueprint $table) {
            $table->unsignedInteger('published_article_id')->nullable()->unique();
            $table->foreign('published_article_id')->references('article_id')->on('articles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fan_submissions', function (Blueprint $table) {
            $table->dropForeign(['published_article_id']);
            $table->dropUnique(['published_article_id']);
            $table->dropColumn('published_article_id');
        });
    }
};
