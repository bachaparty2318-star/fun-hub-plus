<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE media_ratings (
    rating_id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    media_id            INT UNSIGNED        NOT NULL,
    user_id             INT UNSIGNED        NOT NULL,
    rating_value        TINYINT UNSIGNED    NULL,   -- 1-5 stars
    thumbs_value        ENUM('up','down')   NULL,   -- alternative thumbs system
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_rating_media_user UNIQUE (media_id, user_id),  -- one rating per user per media
    CONSTRAINT fk_rating_media FOREIGN KEY (media_id)
        REFERENCES media(media_id) ON DELETE CASCADE,
    CONSTRAINT fk_rating_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT chk_rating_value CHECK (rating_value BETWEEN 1 AND 5 OR rating_value IS NULL),
    CONSTRAINT chk_rating_has_value CHECK (rating_value IS NOT NULL OR thumbs_value IS NOT NULL)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('media_ratings');
    }
};