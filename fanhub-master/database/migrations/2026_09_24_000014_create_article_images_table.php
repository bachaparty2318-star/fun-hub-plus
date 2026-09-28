<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE article_images (
    image_id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id          INT UNSIGNED        NOT NULL,
    image_url           VARCHAR(500)        NOT NULL,
    caption             VARCHAR(255)        NULL,
    display_order       SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
    CONSTRAINT fk_articleimg_article FOREIGN KEY (article_id)
        REFERENCES articles(article_id) ON DELETE CASCADE
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('article_images');
    }
};