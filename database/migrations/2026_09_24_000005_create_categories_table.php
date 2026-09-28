<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE categories (
    category_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(50)         NOT NULL,   -- Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga, Cosplay
    slug                VARCHAR(60)         NOT NULL,
    description         VARCHAR(500)        NULL,
    icon_url            VARCHAR(500)        NULL,
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_category_name UNIQUE (name),
    CONSTRAINT uq_category_slug UNIQUE (slug)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};