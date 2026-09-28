<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE article_timeline_events (
    timeline_event_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id          INT UNSIGNED        NOT NULL,
    event_label         VARCHAR(150)        NOT NULL,
    event_date          DATE                NULL,
    description         VARCHAR(500)        NULL,
    display_order       SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
    CONSTRAINT fk_timeline_article FOREIGN KEY (article_id)
        REFERENCES articles(article_id) ON DELETE CASCADE
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('article_timeline_events');
    }
};