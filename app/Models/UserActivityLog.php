<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivityLog extends Model
{
    protected $table = 'user_activity_log';

    protected $primaryKey = 'activity_id';

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'activity_type', 'reference_type', 'reference_id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
