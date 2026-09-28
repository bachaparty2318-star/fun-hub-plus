<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE bookmarks (
    bookmark_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED        NOT NULL,
    item_type           ENUM('content','article','character','media','merchandise') NOT NULL,
    item_id             INT UNSIGNED        NOT NULL,   -- points to the relevant table's PK based on item_type
    note                VARCHAR(500)        NULL,
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_bookmark_unique UNIQUE (user_id, item_type, item_id),
    CONSTRAINT fk_bookmark_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_bookmark_item (item_type, item_id)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};