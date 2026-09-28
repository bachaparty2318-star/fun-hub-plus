<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE merchandise_items (
    item_id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id         INT UNSIGNED        NOT NULL,
    name                VARCHAR(200)        NOT NULL,
    description         VARCHAR(500)        NULL,
    image_url           VARCHAR(500)        NULL,
    tag                 ENUM('Limited Edition','Pre-Order','Collectible','Standard') NOT NULL DEFAULT 'Standard',
    is_upcoming         TINYINT(1)          NOT NULL DEFAULT 0,
    release_date        DATE                NULL,
    view_count          INT UNSIGNED        NOT NULL DEFAULT 0,   -- optional popularity tracking
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_merch_category FOREIGN KEY (category_id)
        REFERENCES categories(category_id) ON DELETE CASCADE,
    INDEX idx_merch_upcoming (is_upcoming),
    INDEX idx_merch_tag (tag)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_items');
    }
};