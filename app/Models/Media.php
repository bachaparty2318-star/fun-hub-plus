<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $primaryKey = 'media_id';

    public const UPDATED_AT = null;

    protected $fillable = ['content_id', 'media_type', 'media_url', 'duration_seconds', 'uploaded_by'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function ratings()
    {
        return $this->hasMany(MediaRating::class, 'media_id');
    }

    public function bookmarks()
    {
        return $this->morphMany(Bookmark::class, 'item');
    }
}
