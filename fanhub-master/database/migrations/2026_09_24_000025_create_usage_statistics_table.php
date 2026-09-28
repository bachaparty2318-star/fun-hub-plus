<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE usage_statistics (
    stat_id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stat_date           DATE                NOT NULL,
    active_users_count  INT UNSIGNED        NOT NULL DEFAULT 0,
    most_popular_category_id INT UNSIGNED   NULL,
    chatbot_interaction_count INT UNSIGNED  NOT NULL DEFAULT 0,
    new_signups_count   INT UNSIGNED        NOT NULL DEFAULT 0,
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_usage_date UNIQUE (stat_date),
    CONSTRAINT fk_usage_category FOREIGN KEY (most_popular_category_id)
        REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_statistics');
    }
};