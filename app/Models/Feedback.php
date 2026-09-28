<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $primaryKey = 'feedback_id';

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'type', 'message', 'status', 'resolved_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
