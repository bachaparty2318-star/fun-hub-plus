<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchandiseItem extends Model
{
    protected $table = 'merchandise_items';

    protected $primaryKey = 'item_id';

    public const UPDATED_AT = null;

    protected $fillable = ['category_id', 'name', 'description', 'image_url', 'tag', 'is_upcoming', 'release_date', 'view_count', 'fandom_name'];

    protected function casts(): array
    {
        return ['is_upcoming' => 'boolean', 'release_date' => 'date', 'created_at' => 'datetime'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function images()
    {
        return $this->hasMany(MerchandiseImage::class, 'item_id')->orderBy('display_order');
    }

    public function bookmarks()
    {
        return $this->morphMany(Bookmark::class, 'item');
    }
}
