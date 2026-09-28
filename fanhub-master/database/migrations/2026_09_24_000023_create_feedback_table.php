<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE feedback (
    feedback_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED        NULL,   -- visitors can also submit feedback
    type                ENUM('bug','suggestion','query') NOT NULL,
    message             TEXT                NOT NULL,
    status              ENUM('open','in_progress','resolved') NOT NULL DEFAULT 'open',
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at         DATETIME            NULL,
    CONSTRAINT fk_feedback_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_feedback_status (status),
    INDEX idx_feedback_type (type)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};