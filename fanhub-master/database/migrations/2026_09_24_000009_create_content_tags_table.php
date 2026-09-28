<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE content_tags (
    content_id          INT UNSIGNED        NOT NULL,
    tag_id              INT UNSIGNED        NOT NULL,
    PRIMARY KEY (content_id, tag_id),
    CONSTRAINT fk_ctag_content FOREIGN KEY (content_id)
        REFERENCES content(content_id) ON DELETE CASCADE,
    CONSTRAINT fk_ctag_tag FOREIGN KEY (tag_id)
        REFERENCES tags(tag_id) ON DELETE CASCADE
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('content_tags');
    }
};