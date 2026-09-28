<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE media (
    media_id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_id          INT UNSIGNED        NOT NULL,
    media_type          ENUM('video','audio','trailer','animated_explainer','image') NOT NULL,
    media_url           VARCHAR(500)        NOT NULL,
    duration_seconds    INT UNSIGNED        NULL,
    uploaded_by         INT UNSIGNED        NULL,   -- admin
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_media_content FOREIGN KEY (content_id)
        REFERENCES content(content_id) ON DELETE CASCADE,
    CONSTRAINT fk_media_admin FOREIGN KEY (uploaded_by)
        REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_media_content (content_id)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};