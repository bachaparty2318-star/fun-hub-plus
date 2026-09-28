<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE user_sessions (
    session_id          VARCHAR(128)        PRIMARY KEY,   -- e.g. UUID/JWT-id
    user_id             INT UNSIGNED        NOT NULL,
    ip_address          VARCHAR(45)         NULL,
    user_agent          VARCHAR(255)        NULL,
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at          DATETIME            NOT NULL,
    is_revoked          TINYINT(1)          NOT NULL DEFAULT 0,
    CONSTRAINT fk_session_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};