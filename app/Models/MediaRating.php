<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaRating extends Model
{
    protected $table = 'media_ratings';

    protected $primaryKey = 'rating_id';

    public const UPDATED_AT = null;

    protected $fillable = ['media_id', 'user_id', 'rating_value', 'thumbs_value'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
