<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE events (
    event_id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id         INT UNSIGNED        NULL,   -- optional link to a fandom
    title               VARCHAR(200)        NOT NULL,
    description         VARCHAR(1000)       NULL,
    event_type          ENUM('convention','premiere','release','meetup','screening') NOT NULL,
    event_date          DATETIME            NOT NULL,
    end_date            DATETIME            NULL,
    city                VARCHAR(100)        NOT NULL,
    venue               VARCHAR(200)        NULL,
    latitude            DECIMAL(9,6)        NULL,   -- for Map/GPS integration
    longitude           DECIMAL(9,6)        NULL,
    ticket_link         VARCHAR(500)        NULL,
    created_by          INT UNSIGNED        NULL,
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_category FOREIGN KEY (category_id)
        REFERENCES categories(category_id) ON DELETE SET NULL,
    CONSTRAINT fk_event_admin FOREIGN KEY (created_by)
        REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_event_city (city),
    INDEX idx_event_date (event_date),
    INDEX idx_event_geo (latitude, longitude)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};