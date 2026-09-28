<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\PublicCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookmarkController extends Controller
{
    public function index(Request $request, PublicCatalog $catalog)
    {
        $data = $request->validate([
            'item_type' => 'sometimes|in:content,article,character,media,merchandise',
            'per_page' => 'sometimes|integer|min:1|max:100', 'page' => 'sometimes|integer|min:1',
        ]);
        $query = $request->user()->bookmarks()->with('item')->orderByDesc('bookmark_id');
        if (isset($data['item_type'])) {
            $query->where('item_type', $data['item_type']);
        }
        $results = $query->paginate($data['per_page'] ?? 20)->withQueryString();
        $results->through(function ($bookmark) use ($catalog) {
            $data = $bookmark->only(['bookmark_id', 'item_type', 'item_id', 'note', 'created_at']);
            $data['item'] = $bookmark->item ? $catalog->summary($bookmark->item) : null;

            return $data;
        });

        return $results;
    }

    public function store(Request $request, PublicCatalog $catalog)
    {
        $data = $request->validate([
            'item_type' => 'required|in:content,article,character,media,merchandise',
            'item_id' => 'required|integer|min:1', 'note' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($request, $catalog, $data) {
            User::whereKey($request->user()->getKey())->lockForUpdate()->firstOrFail();
            $catalog->find($data['item_type'], $data['item_id'], true);
            $bookmark = Bookmark::firstOrNew([
                'user_id' => $request->user()->getKey(), 'item_type' => $data['item_type'], 'item_id' => $data['item_id'],
            ]);
            $created = ! $bookmark->exists;
            if (array_key_exists('note', $data)) {
                $bookmark->note = $data['note'];
            }
            $bookmark->save();
            if ($created) {
                UserActivityLog::create(['user_id' => $request->user()->getKey(), 'activity_type' => 'bookmark', 'reference_type' => $data['item_type'], 'reference_id' => $data['item_id']]);
            }

            return response()->json(['data' => $bookmark], $created ? 201 : 200);
        }, 3);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate(['note' => 'present|nullable|string|max:500']);
        $bookmark = $request->user()->bookmarks()->findOrFail($id);
        $bookmark->update($data);

        return response()->json(['data' => $bookmark]);
    }

    public function destroy(Request $request, int $id)
    {
        $request->user()->bookmarks()->findOrFail($id)->delete();

        return response()->noContent();
    }
}
