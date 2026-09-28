<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $table = 'content';

    protected $primaryKey = 'content_id';

    protected $fillable = ['category_id', 'title', 'type', 'genre', 'description', 'thumbnail_url', 'release_date', 'popularity_score', 'view_count', 'created_by', 'fandom_name'];

    protected function casts(): array
    {
        return ['release_date' => 'date', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function media()
    {
        return $this->hasMany(Media::class, 'content_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'content_tags', 'content_id', 'tag_id');
    }

    public function bookmarks()
    {
        return $this->morphMany(Bookmark::class, 'item');
    }
}
