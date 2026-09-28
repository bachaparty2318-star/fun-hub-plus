<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW vw_content_bookmark_counts AS
SELECT c.content_id, c.title, c.category_id, COUNT(b.bookmark_id) AS bookmark_count
FROM content c
LEFT JOIN bookmarks b ON b.item_type = 'content' AND b.item_id = c.content_id
GROUP BY c.content_id, c.title, c.category_id;
SQL);
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW vw_media_average_rating AS
SELECT media_id,
       ROUND(AVG(rating_value), 2) AS avg_rating,
       SUM(CASE WHEN thumbs_value = 'up' THEN 1 ELSE 0 END)   AS thumbs_up,
       SUM(CASE WHEN thumbs_value = 'down' THEN 1 ELSE 0 END) AS thumbs_down,
       COUNT(*) AS total_ratings
FROM media_ratings
GROUP BY media_id;
SQL);
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW vw_pending_fan_submissions AS
SELECT fs.submission_id, fs.title, fs.status, u.name AS submitted_by, fs.created_at
FROM fan_submissions fs
JOIN users u ON u.user_id = fs.user_id
WHERE fs.status = 'pending'
ORDER BY fs.created_at ASC;
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_content_bookmark_counts');
        DB::statement('DROP VIEW IF EXISTS vw_media_average_rating');
        DB::statement('DROP VIEW IF EXISTS vw_pending_fan_submissions');
    }
};