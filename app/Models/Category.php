<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $primaryKey = 'category_id';

    public const UPDATED_AT = null;

    protected $fillable = ['name', 'slug', 'description', 'icon_url'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function content()
    {
        return $this->hasMany(Content::class, 'category_id');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'category_id');
    }

    public function characters()
    {
        return $this->hasMany(CharacterProfile::class, 'category_id');
    }

    public function merchandise()
    {
        return $this->hasMany(MerchandiseItem::class, 'category_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'category_id');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_favorite_categories', 'category_id', 'user_id');
    }
}
