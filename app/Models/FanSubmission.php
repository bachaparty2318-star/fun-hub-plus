<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FanSubmission extends Model
{
    protected $table = 'fan_submissions';

    protected $primaryKey = 'submission_id';

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'category_id', 'title', 'body_html', 'cover_image_url', 'status', 'reviewed_by', 'review_notes', 'reviewed_at', 'published_article_id'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function publishedArticle()
    {
        return $this->belongsTo(Article::class, 'published_article_id');
    }
}
