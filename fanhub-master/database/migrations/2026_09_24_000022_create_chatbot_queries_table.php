<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE chatbot_queries (
    query_id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED        NULL,
    session_id          VARCHAR(128)        NOT NULL,   -- groups a visitor's conversation
    message             TEXT                NOT NULL,
    response            TEXT                NOT NULL,
    matched_faq_id       INT UNSIGNED        NULL,
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_chatbot_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_chatbot_faq FOREIGN KEY (matched_faq_id)
        REFERENCES chatbot_faq(faq_id) ON DELETE SET NULL,
    INDEX idx_chatbot_session (session_id)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_queries');
    }
};