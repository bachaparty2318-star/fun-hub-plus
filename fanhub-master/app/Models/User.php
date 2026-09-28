<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    protected $attributes = ['role' => 'registered', 'is_active' => true];

    protected $fillable = ['name', 'email', 'password_hash'];

    protected $hidden = ['password_hash', 'remember_token'];

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime', 'password_hash' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    public function favoriteCategories()
    {
        return $this->belongsToMany(Category::class, 'user_favorite_categories', 'user_id', 'category_id');
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class, 'user_id');
    }

    public function submissions()
    {
        return $this->hasMany(FanSubmission::class, 'user_id');
    }

    public function activity()
    {
        return $this->hasMany(UserActivityLog::class, 'user_id');
    }

    public function ratings()
    {
        return $this->hasMany(MediaRating::class, 'user_id');
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class, 'user_id');
    }
}
