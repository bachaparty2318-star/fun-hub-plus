<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE tags (
    tag_id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tag_name            VARCHAR(50)         NOT NULL,
    CONSTRAINT uq_tag_name UNIQUE (tag_name)
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};