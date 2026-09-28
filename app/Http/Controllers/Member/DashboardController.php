<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\PublicCatalog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, PublicCatalog $catalog)
    {
        $user = $request->user()->load(['profile', 'favoriteCategories']);
        $bookmarks = $user->bookmarks()->with('item')->orderByDesc('bookmark_id')->limit(8)->get()->map(function ($bookmark) use ($catalog) {
            return array_merge($bookmark->only(['bookmark_id', 'item_type', 'item_id', 'note']), [
                'item' => $bookmark->item ? $catalog->summary($bookmark->item) : null,
            ]);
        });

        return response()->json(['data' => [
            'greeting' => 'Hello, '.$user->name.'!',
            'profile' => $user->profile, 'favorite_categories' => $user->favoriteCategories,
            'favorite_fandoms' => $user->profile?->favorite_fandoms ?? [],
            'bookmarks' => $bookmarks, 'recent_activity' => $user->activity()->orderByDesc('activity_id')->limit(10)->get(),
            'counts' => [
                'bookmarks' => $user->bookmarks()->count(), 'ratings' => $user->ratings()->count(),
                'submissions' => $user->submissions()->count(), 'pending_submissions' => $user->submissions()->where('status', 'pending')->count(),
                'feedback' => $user->feedback()->count(),
            ],
        ]]);
    }

    public function activity(Request $request)
    {
        $data = $request->validate(['page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:100']);

        return $request->user()->activity()->orderByDesc('activity_id')->paginate($data['per_page'] ?? 20);
    }
}
