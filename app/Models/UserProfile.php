<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $table = 'user_profiles';

    protected $primaryKey = 'profile_id';

    protected $fillable = ['user_id', 'avatar_url', 'bio', 'dark_mode_enabled', 'font_size', 'display_preferences', 'favorite_fandoms'];

    protected function casts(): array
    {
        return ['dark_mode_enabled' => 'boolean', 'display_preferences' => 'array', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'favorite_fandoms' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
