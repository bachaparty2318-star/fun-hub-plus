<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchandiseImage extends Model
{
    protected $table = 'merchandise_images';

    protected $primaryKey = 'image_id';

    public $timestamps = false;

    protected $fillable = ['item_id', 'image_url', 'display_order'];

    public function item()
    {
        return $this->belongsTo(MerchandiseItem::class, 'item_id');
    }
}
