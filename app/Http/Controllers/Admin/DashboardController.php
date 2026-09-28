<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Media;
use App\Models\MediaRating;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $counts = [];
        foreach (['users', 'categories', 'content', 'media', 'articles', 'character_profiles', 'merchandise_items', 'events'] as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return response()->json(['data' => [
            'totals' => $counts,
            'active_users_30_days' => DB::table('user_activity_log')->join('users', 'users.user_id', '=', 'user_activity_log.user_id')
                ->where('users.is_active', true)->where('user_activity_log.created_at', '>=', now()->subDays(30))
                ->distinct()->count('user_activity_log.user_id'),
            'online_users_15_minutes' => DB::table('sessions')->join('users', 'users.user_id', '=', 'sessions.user_id')
                ->where('users.is_active', true)->where('last_activity', '>=', now()->subMinutes(15)->timestamp)
                ->distinct()->count('sessions.user_id'),
            'pending_submissions' => DB::table('fan_submissions')->where('status', 'pending')->count(),
            'open_feedback' => DB::table('feedback')->whereIn('status', ['open', 'in_progress'])->count(),
            'upcoming_events' => DB::table('events')->where('event_date', '>=', now())->count(),
            'upcoming_releases' => DB::table('content')->where('release_date', '>', today())->count(),
            'popular_categories' => Category::withCount('followers')->withSum('content as content_views', 'view_count')
                ->orderByDesc('followers_count')->orderByDesc('content_views')->orderBy('category_id')->limit(8)->get(),
            'category_popularity_basis' => 'Favorite-category followers, then content views.',
        ]]);
    }

    public function activity(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'sometimes|integer|exists:users,user_id', 'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);
        $query = UserActivityLog::with('user:user_id,name')->orderByDesc('activity_id');
        if (isset($data['user_id'])) {
            $query->where('user_id', $data['user_id']);
        }

        return $query->paginate($data['per_page'] ?? 20);
    }

    public function ratings(Request $request)
    {
        $data = $request->validate([
            'media_id' => 'sometimes|integer|exists:media,media_id', 'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);
        $query = MediaRating::with(['user:user_id,name', 'media'])->orderByDesc('rating_id');
        if (isset($data['media_id'])) {
            $query->where('media_id', $data['media_id']);
        }

        return $query->paginate($data['per_page'] ?? 20);
    }

    public function ratingSummary(int $id)
    {
        Media::findOrFail($id);

        return response()->json(['data' => DB::table('vw_media_average_rating')->where('media_id', $id)->first()
            ?? ['media_id' => $id, 'avg_rating' => null, 'thumbs_up' => 0, 'thumbs_down' => 0, 'total_ratings' => 0]]);
    }
}
