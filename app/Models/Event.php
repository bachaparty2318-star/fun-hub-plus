<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';

    protected $primaryKey = 'event_id';

    public const UPDATED_AT = null;

    protected $fillable = ['category_id', 'title', 'description', 'event_type', 'event_date', 'end_date', 'city', 'venue', 'latitude', 'longitude', 'ticket_link', 'created_by'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'event_date' => 'datetime', 'end_date' => 'datetime', 'latitude' => 'decimal:6', 'longitude' => 'decimal:6'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
