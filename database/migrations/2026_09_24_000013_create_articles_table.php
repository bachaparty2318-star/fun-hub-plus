<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE articles (
    article_id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id         INT UNSIGNED        NOT NULL,
    author_id           INT UNSIGNED        NULL,     -- admin/editor
    title               VARCHAR(200)        NOT NULL,
    body_html           LONGTEXT            NOT NULL, -- rich text content
    cover_image_url     VARCHAR(500)        NULL,
    is_featured         TINYINT(1)          NOT NULL DEFAULT 0,
    published_at        DATETIME            NULL,
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_article_category FOREIGN KEY (category_id)
        REFERENCES categories(category_id) ON DELETE CASCADE,
    CONSTRAINT fk_article_author FOREIGN KEY (author_id)
        REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_article_featured (is_featured)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};