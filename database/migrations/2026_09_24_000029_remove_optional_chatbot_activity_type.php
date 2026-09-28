<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $count = DB::table('user_activity_log')->where('activity_type', 'chatbot_interaction')->count();
        if ($count > 0) {
            throw new RuntimeException('Cannot remove chatbot_interaction activity type while rows still use it.');
        }

        DB::statement("ALTER TABLE user_activity_log MODIFY activity_type ENUM('view_content','bookmark','rate_media','submit_fan_content','login','update_profile') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE user_activity_log MODIFY activity_type ENUM('view_content','bookmark','rate_media','submit_fan_content','chatbot_interaction','login','update_profile') NOT NULL");
    }
};
