<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MediaRating;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\PublicCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RatingController extends Controller
{
    public function show(Request $request, int $id, PublicCatalog $catalog)
    {
        $catalog->find('media', $id);

        return response()->json(['data' => $request->user()->ratings()->where('media_id', $id)->first()]);
    }

    public function store(Request $request, int $id, PublicCatalog $catalog)
    {
        $data = $request->validate(['rating_value' => 'nullable|integer|between:1,5', 'thumbs_value' => 'nullable|in:up,down']);
        if (isset($data['rating_value']) === isset($data['thumbs_value'])) {
            throw ValidationException::withMessages(['rating_value' => 'Supply either 1-5 stars or a thumbs value, not both.']);
        }

        return DB::transaction(function () use ($request, $id, $catalog, $data) {
            User::whereKey($request->user()->getKey())->lockForUpdate()->firstOrFail();
            $catalog->find('media', $id, true);
            $rating = MediaRating::updateOrCreate(
                ['media_id' => $id, 'user_id' => $request->user()->getKey()],
                ['rating_value' => $data['rating_value'] ?? null, 'thumbs_value' => $data['thumbs_value'] ?? null],
            );
            UserActivityLog::create(['user_id' => $request->user()->getKey(), 'activity_type' => 'rate_media', 'reference_type' => 'media', 'reference_id' => $id]);

            return response()->json(['data' => $rating, 'summary' => DB::table('vw_media_average_rating')->where('media_id', $id)->first()]);
        }, 3);
    }

    public function destroy(Request $request, int $id)
    {
        $request->user()->ratings()->where('media_id', $id)->firstOrFail()->delete();

        return response()->noContent();
    }
}
