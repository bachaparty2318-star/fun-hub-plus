<?php

namespace App\Http\Controllers\Visitor;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\CharacterProfile;
use App\Models\Event;
use App\Models\Media;
use App\Models\MerchandiseItem;
use App\Services\PublicCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PageController extends Controller
{
    public function home(PublicCatalog $catalog)
    {
        if (! Schema::hasTable('categories')) {
            $empty = collect();

            return view('welcome', [
                'categories' => $empty, 'featured' => $empty, 'latest' => $empty,
                'articles' => $empty, 'characters' => $empty, 'media' => $empty,
                'merchandise' => $empty, 'events' => $empty,
            ]);
        }

        $categories = Category::orderBy('category_id')->get();
        $featured = $catalog->explorer(['sort' => 'popular'])->limit(6)->get();
        $latest = $catalog->explorer(['sort' => 'latest'])->limit(8)->get();
        $articles = Article::with('category:category_id,name,slug')
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->orderByDesc('is_featured')->orderByDesc('published_at')->limit(5)->get();
        $characters = CharacterProfile::with('category:category_id,name,slug')
            ->orderByDesc('popularity_score')->orderBy('name')->limit(8)->get();
        $media = Media::with('content:content_id,title,thumbnail_url,fandom_name,category_id')
            ->latest('media_id')->limit(6)->get();
        $merchandise = MerchandiseItem::with('category:category_id,name,slug')
            ->latest('item_id')->limit(6)->get();
        $events = Event::with('category:category_id,name,slug')->where('event_date', '>=', now()->subDay())
            ->orderBy('event_date')->limit(5)->get();

        return view('welcome', compact('categories', 'featured', 'latest', 'articles', 'characters', 'media', 'merchandise', 'events'));
    }

    public function category(Request $request, string $slug, PublicCatalog $catalog)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $filters = $request->validate([
            'genre' => 'nullable|string|max:100',
            'content_type' => 'nullable|string|max:40',
            'sort' => 'nullable|in:latest,popular,alphabetical,release_date',
            'min_popularity' => 'nullable|integer|min:0|max:4294967295',
            'page' => 'nullable|integer|min:1',
        ]);
        $filters['category_id'] = $category->getKey();
        $filters['per_page'] = 12;
        $items = $catalog->explorer($filters)->paginate(12)->withQueryString();
        $genres = $catalog->explorer(['category_id' => $category->getKey()])
            ->whereNotNull('genre')->distinct()->orderBy('genre')->pluck('genre');

        return view('visitor.category', compact('category', 'items', 'genres'));
    }

    public function content(Request $request, string $type, int $id, PublicCatalog $catalog)
    {
        $item = $catalog->find($type, $id);
        $data = $catalog->data($item, 'visitor.content.typed');
        abort_unless($data, 404);
        $category = ! empty($data['category']['category_id'])
            ? Category::find($data['category']['category_id'])
            : null;
        $related = $category
            ? $catalog->explorer(['category_id' => $category->getKey(), 'per_page' => 8])->limit(8)->get()
            : collect();

        return view('visitor.content-detail', compact('data', 'category', 'related'));
    }

    public function contentById(Request $request, int $id, PublicCatalog $catalog)
    {
        return $this->content($request, 'content', $id, $catalog);
    }

    public function characters(Request $request)
    {
        $filters = $request->validate([
            'category_id' => 'nullable|integer|exists:categories,category_id',
            'fandom_name' => 'nullable|string|max:150',
            'sort' => 'nullable|in:latest,popular,alphabetical',
            'page' => 'nullable|integer|min:1',
        ]);
        $query = CharacterProfile::with('category:category_id,name,slug');
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (! empty($filters['fandom_name'])) {
            $query->where('fandom_name', 'like', '%'.$filters['fandom_name'].'%');
        }
        match ($filters['sort'] ?? 'popular') {
            'alphabetical' => $query->orderBy('name'),
            'latest' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('popularity_score')->orderBy('name'),
        };
        $characters = $query->paginate(12)->withQueryString();
        $categories = Category::orderBy('name')->get(['category_id', 'name']);

        return view('visitor.characters', compact('characters', 'categories'));
    }

    public function character(int $id, PublicCatalog $catalog)
    {
        $character = CharacterProfile::with('category:category_id,name,slug')->findOrFail($id);
        $related = $catalog->explorer(['category_id' => $character->category_id, 'per_page' => 8])->limit(8)->get();

        return view('visitor.character-detail', compact('character', 'related'));
    }

    public function articles(Request $request)
    {
        $filters = $request->validate(['category_id' => 'nullable|integer|exists:categories,category_id', 'page' => 'nullable|integer|min:1']);
        $query = Article::with('category:category_id,name,slug')->whereNotNull('published_at')->where('published_at', '<=', now());
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        $featured = (clone $query)->where('is_featured', true)->orderByDesc('published_at')->limit(3)->get();
        $articles = $query->orderByDesc('is_featured')->orderByDesc('published_at')->paginate(9)->withQueryString();
        $categories = Category::orderBy('name')->get(['category_id', 'name']);

        return view('visitor.articles', compact('articles', 'featured', 'categories'));
    }

    public function article(int $id)
    {
        $article = Article::with(['category:category_id,name,slug', 'images', 'timeline'])
            ->whereNotNull('published_at')->where('published_at', '<=', now())->findOrFail($id);
        $related = Article::where('category_id', $article->category_id)->whereKeyNot($article->getKey())
            ->whereNotNull('published_at')->where('published_at', '<=', now())->orderByDesc('published_at')->limit(4)->get();

        return view('visitor.article-detail', compact('article', 'related'));
    }

    public function merchandise(Request $request)
    {
        $filters = $request->validate(['tag' => 'nullable|in:Limited Edition,Pre-Order,Collectible,Standard', 'page' => 'nullable|integer|min:1']);
        $query = MerchandiseItem::with('category:category_id,name,slug');
        if (! empty($filters['tag'])) {
            $query->where('tag', $filters['tag']);
        }
        $items = $query->orderByDesc('created_at')->paginate(12)->withQueryString();

        return view('visitor.merchandise', compact('items'));
    }

    public function merchandiseDetail(int $id)
    {
        $item = MerchandiseItem::with(['category:category_id,name,slug', 'images'])->findOrFail($id);

        return view('visitor.merchandise-detail', compact('item'));
    }

    public function events(Request $request)
    {
        $filters = $request->validate(['city' => 'nullable|string|max:100', 'event_type' => 'nullable|string|max:100']);
        $query = Event::with('category:category_id,name,slug')->orderBy('event_date');
        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }
        if (! empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }
        $events = $query->paginate(20)->withQueryString();
        $cities = Event::whereNotNull('city')->distinct()->orderBy('city')->pluck('city');
        $types = Event::whereNotNull('event_type')->distinct()->orderBy('event_type')->pluck('event_type');

        return view('visitor.events', compact('events', 'cities', 'types'));
    }

    public function explore(Request $request, PublicCatalog $catalog)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:200', 'category_id' => 'nullable|integer|exists:categories,category_id',
            'genre' => 'nullable|string|max:100', 'content_type' => 'nullable|string|max:40',
            'release_year' => 'nullable|integer|between:1000,9999', 'min_popularity' => 'nullable|integer|min:0',
            'sort' => 'nullable|in:latest,popular,alphabetical,release_date', 'page' => 'nullable|integer|min:1',
        ]);
        $filters['per_page'] = 12;
        $items = $catalog->explorer($filters)->paginate(12)->withQueryString();
        $categories = Category::orderBy('name')->get(['category_id', 'name']);

        return view('visitor.explore', compact('items', 'categories'));
    }

    public function auth(string $mode = 'login', ?string $token = null)
    {
        abort_unless(in_array($mode, ['login', 'register', 'forgot', 'reset'], true), 404);

        return view('visitor.auth', compact('mode', 'token'));
    }
}
