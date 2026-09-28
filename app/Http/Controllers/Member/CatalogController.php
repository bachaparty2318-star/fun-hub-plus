<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Content;
use App\Models\Media;
use App\Models\MerchandiseItem;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\PublicCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CatalogController extends Controller
{
    public function categories()
    {
        return response()->json(['data' => Category::orderBy('name')->get(['category_id', 'name', 'slug', 'description', 'icon_url'])]);
    }

    public function tags(Request $request)
    {
        $data = $request->validate(['q' => 'nullable|string|max:50', 'per_page' => 'sometimes|integer|min:1|max:100', 'page' => 'sometimes|integer|min:1']);
        $query = Tag::orderBy('tag_name');
        if (! empty($data['q'])) {
            $query->where('tag_name', 'like', '%'.$data['q'].'%');
        }

        return $query->paginate($data['per_page'] ?? 50);
    }

    public function index(Request $request, PublicCatalog $catalog)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:200', 'category_id' => 'sometimes|integer|exists:categories,category_id',
            'genre' => 'nullable|string|max:100', 'fandom_name' => 'nullable|string|max:150',
            'content_type' => 'sometimes|in:article,character,video,audio,image,trailer,animated_explainer,release,merchandise',
            'item_type' => 'sometimes|in:content,article,character,media,merchandise',
            'release_year' => 'sometimes|integer|between:1000,9999', 'min_popularity' => 'sometimes|integer|min:0|max:4294967295',
            'is_featured' => 'sometimes|boolean', 'upcoming' => 'sometimes|boolean',
            'tag_id' => 'sometimes|integer|exists:tags,tag_id',
            'merchandise_tag' => ['sometimes', Rule::in(['Limited Edition', 'Pre-Order', 'Collectible', 'Standard'])],
            'sort' => 'sometimes|in:latest,popular,alphabetical,release_date',
            'per_page' => 'sometimes|integer|min:1|max:100', 'page' => 'sometimes|integer|min:1',
        ]);
        if ($request->routeIs('member.upcoming') || $request->routeIs('visitor.upcoming')) {
            $data['upcoming'] = true;
            $data['sort'] ??= 'release_date';
        }
        $results = $catalog->explorer($data)->paginate($data['per_page'] ?? 20)->withQueryString();
        $route = $request->is('visitor/api/*') ? 'visitor.catalog.show' : 'member.catalog.show';
        foreach ($results as $item) {
            $item->share_url = route($route, ['type' => $item->item_type, 'id' => $item->item_id]);
        }

        return $results;
    }

    public function show(Request $request, string $type, int $id, PublicCatalog $catalog)
    {
        $item = $catalog->find($type, $id);
        $route = $request->is('visitor/api/*') ? 'visitor.catalog.show' : 'member.catalog.show';
        $user = $request->user();
        if ($user && $user->is_active && $user->hasVerifiedEmail()) {
            DB::transaction(function () use ($user, $type, $id, $catalog) {
                User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();
                $item = $catalog->find($type, $id, true);
                $alreadyViewed = $user->activity()->where('activity_type', 'view_content')
                    ->where('reference_type', $type)->where('reference_id', $id)->where('created_at', '>=', now()->subMinutes(10))->exists();
                if (! $alreadyViewed) {
                    UserActivityLog::create(['user_id' => $user->getKey(), 'activity_type' => 'view_content', 'reference_type' => $type, 'reference_id' => $id]);
                    if ($item instanceof Content || $item instanceof MerchandiseItem) {
                        $item->increment('view_count');
                    }
                    if ($item instanceof Media) {
                        $item->content()->increment('view_count');
                    }
                }
            }, 3);
            $item->refresh();
        }

        return response()->json(['data' => $catalog->data($item, $route)]);
    }

    public function share(Request $request, string $type, int $id, PublicCatalog $catalog)
    {
        $item = $catalog->find($type, $id);
        $route = $request->is('visitor/api/*') ? 'visitor.catalog.show' : 'member.catalog.show';

        return response()->json(['data' => [
            'title' => $item->title ?? $item->name ?? $item->content?->title,
            'url' => route($route, ['type' => $type, 'id' => $id]),
        ]]);
    }
}
