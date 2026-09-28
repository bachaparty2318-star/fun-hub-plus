<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE content (
    content_id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id         INT UNSIGNED        NOT NULL,
    title               VARCHAR(200)        NOT NULL,
    type                ENUM('article','video','audio','image','trailer') NOT NULL,
    genre               VARCHAR(100)        NULL,
    description         TEXT                NULL,
    thumbnail_url       VARCHAR(500)        NULL,
    release_date        DATE                NULL,
    popularity_score    INT UNSIGNED        NOT NULL DEFAULT 0,
    view_count          INT UNSIGNED        NOT NULL DEFAULT 0,
    created_by          INT UNSIGNED        NULL,   -- admin who added it
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_content_category FOREIGN KEY (category_id)
        REFERENCES categories(category_id) ON DELETE CASCADE,
    CONSTRAINT fk_content_admin FOREIGN KEY (created_by)
        REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_content_category (category_id),
    INDEX idx_content_type (type),
    INDEX idx_content_release (release_date),
    INDEX idx_content_popularity (popularity_score)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('content');
    }
};