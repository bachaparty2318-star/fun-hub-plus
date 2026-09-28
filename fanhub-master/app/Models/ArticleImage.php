<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleImage extends Model
{
    protected $table = 'article_images';

    protected $primaryKey = 'image_id';

    public $timestamps = false;

    protected $fillable = ['article_id', 'image_url', 'caption', 'display_order'];

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
