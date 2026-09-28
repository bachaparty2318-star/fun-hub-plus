<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleTimelineEvent extends Model
{
    protected $table = 'article_timeline_events';

    protected $primaryKey = 'timeline_event_id';

    public $timestamps = false;

    protected $fillable = ['article_id', 'event_label', 'event_date', 'description', 'display_order'];

    protected function casts(): array
    {
        return ['event_date' => 'date'];
    }

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
