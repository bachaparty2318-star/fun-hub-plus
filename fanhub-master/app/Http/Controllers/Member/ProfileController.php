<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Rules\SafeUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(['data' => $request->user()->load(['profile', 'favoriteCategories'])]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'avatar_url' => ['nullable', 'string', 'max:500', new SafeUrl],
            'bio' => 'nullable|string|max:500', 'dark_mode_enabled' => 'sometimes|boolean',
            'font_size' => 'sometimes|in:small,medium,large',
            'favorite_fandoms' => 'nullable|array|max:50', 'favorite_fandoms.*' => 'string|max:150|distinct',
            'category_ids' => 'sometimes|array|max:50', 'category_ids.*' => 'integer|distinct|exists:categories,category_id',
            'display_preferences' => 'nullable|array:default_sort,content_layout',
            'display_preferences.default_sort' => 'sometimes|in:latest,popular,alphabetical',
            'display_preferences.content_layout' => 'sometimes|in:grid,list',
        ]);

        return DB::transaction(function () use ($request, $data) {
            $user = $request->user();
            // Prevent simultaneous first profile creation / favorite replacement.
            User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            if (isset($data['name'])) {
                $user->update(['name' => $data['name']]);
            }
            $categories = $data['category_ids'] ?? null;
            unset($data['name'], $data['category_ids']);
            $user->profile()->updateOrCreate(['user_id' => $user->getKey()], $data);
            if ($categories !== null) {
                $user->favoriteCategories()->sync($categories);
            }
            UserActivityLog::create(['user_id' => $user->getKey(), 'activity_type' => 'update_profile']);

            return response()->json(['data' => $user->load(['profile', 'favoriteCategories'])]);
        });
    }

    public function avatar(Request $request)
    {
        $request->validate(['file' => 'required|file|image|mimes:jpg,jpeg,png,webp,gif|max:5120']);
        $path = $request->file('file')->store('avatars/'.$request->user()->getKey(), 'public');
        abort_unless($path, 500, 'Avatar could not be saved.');
        try {
            $url = Storage::disk('public')->url($path);
            DB::transaction(function () use ($request, $url) {
                $request->user()->profile()->updateOrCreate(['user_id' => $request->user()->getKey()], ['avatar_url' => $url]);
                UserActivityLog::create(['user_id' => $request->user()->getKey(), 'activity_type' => 'update_profile']);
            });

            return response()->json(['data' => ['avatar_url' => $url]], 201);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
    }
}
