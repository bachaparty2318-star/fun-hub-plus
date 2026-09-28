<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE merchandise_images (
    image_id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id             INT UNSIGNED        NOT NULL,
    image_url           VARCHAR(500)        NOT NULL,
    display_order       SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
    CONSTRAINT fk_merchimg_item FOREIGN KEY (item_id)
        REFERENCES merchandise_items(item_id) ON DELETE CASCADE
) ENGINE=InnoDB;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_images');
    }
};