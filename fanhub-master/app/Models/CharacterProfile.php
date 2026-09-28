<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterProfile extends Model
{
    protected $table = 'character_profiles';

    protected $primaryKey = 'character_id';

    public const UPDATED_AT = null;

    protected $fillable = ['category_id', 'fandom_name', 'name', 'bio', 'image_url', 'created_by', 'genre', 'release_date', 'popularity_score'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'release_date' => 'date'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bookmarks()
    {
        return $this->morphMany(Bookmark::class, 'item');
    }
}
