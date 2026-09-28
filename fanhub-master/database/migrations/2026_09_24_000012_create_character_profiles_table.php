<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE character_profiles (
    character_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id         INT UNSIGNED        NOT NULL,
    fandom_name         VARCHAR(150)        NULL,     -- e.g. specific anime/game title within category
    name                VARCHAR(150)        NOT NULL,
    bio                 TEXT                NULL,
    image_url           VARCHAR(500)        NULL,
    created_by          INT UNSIGNED        NULL,
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_character_category FOREIGN KEY (category_id)
        REFERENCES categories(category_id) ON DELETE CASCADE,
    CONSTRAINT fk_character_admin FOREIGN KEY (created_by)
        REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_character_category (category_id),
    INDEX idx_character_fandom (fandom_name)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('character_profiles');
    }
};