<?php

namespace App\Services;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Content;
use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdminDeletion
{
    // Called inside the controller transaction, after locking the record.
    public function delete(Model $record): void
    {
        if ($record instanceof Category) {
            foreach (['content', 'articles', 'character_profiles', 'merchandise_items', 'events', 'fan_submissions', 'user_favorite_categories'] as $table) {
                abort_if(DB::table($table)->where('category_id', $record->getKey())->exists(), 409, 'This category is in use. Reassign its records before deleting it.');
            }
        }
        if ($record instanceof Content) {
            Bookmark::where('item_type', 'media')->whereIn('item_id', Media::where('content_id', $record->getKey())->select('media_id'))->delete();
        }
        $alias = $record->getMorphClass();
        if (in_array($alias, ['content', 'article', 'character', 'media', 'merchandise'], true)) {
            Bookmark::where('item_type', $alias)->where('item_id', $record->getKey())->delete();
        }
        $record->delete();
    }
}
