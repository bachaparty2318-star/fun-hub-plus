<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'articles';

    protected $primaryKey = 'article_id';

    protected $fillable = ['category_id', 'author_id', 'title', 'body_html', 'cover_image_url', 'is_featured', 'published_at', 'fandom_name', 'genre', 'release_date', 'popularity_score'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'published_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'release_date' => 'date'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function images()
    {
        return $this->hasMany(ArticleImage::class, 'article_id')->orderBy('display_order');
    }

    public function timeline()
    {
        return $this->hasMany(ArticleTimelineEvent::class, 'article_id')->orderBy('display_order');
    }

    public function bookmarks()
    {
        return $this->morphMany(Bookmark::class, 'item');
    }
}
