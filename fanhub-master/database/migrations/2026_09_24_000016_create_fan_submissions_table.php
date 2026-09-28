<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE fan_submissions (
    submission_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED        NOT NULL,
    category_id         INT UNSIGNED        NOT NULL,
    title               VARCHAR(200)        NOT NULL,
    body_html           LONGTEXT            NOT NULL,
    cover_image_url     VARCHAR(500)        NULL,
    status              ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by         INT UNSIGNED        NULL,   -- admin
    review_notes        VARCHAR(500)        NULL,
    reviewed_at         DATETIME            NULL,
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_submission_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_submission_category FOREIGN KEY (category_id)
        REFERENCES categories(category_id) ON DELETE CASCADE,
    CONSTRAINT fk_submission_reviewer FOREIGN KEY (reviewed_by)
        REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_submission_status (status)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('fan_submissions');
    }
};