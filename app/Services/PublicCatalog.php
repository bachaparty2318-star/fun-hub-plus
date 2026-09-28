<?php

namespace App\Services;

use App\Models\Article;
use App\Models\CharacterProfile;
use App\Models\Content;
use App\Models\Media;
use App\Models\MerchandiseItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PublicCatalog
{
    public const TYPES = [
        'content' => Content::class, 'article' => Article::class,
        'character' => CharacterProfile::class, 'media' => Media::class,
        'merchandise' => MerchandiseItem::class,
    ];

    public function find(string $type, int $id, bool $lock = false): Model
    {
        abort_unless(isset(self::TYPES[$type]), 404, 'Unknown content type.');
        // Match the parent-before-child locking order used by content deletion.
        if ($type === 'media' && $lock) {
            $parent = Media::findOrFail($id)->content_id;
            Content::whereKey($parent)->lockForUpdate()->firstOrFail();
        }
        $query = self::TYPES[$type]::query();
        if ($type === 'article') {
            $query->whereNotNull('published_at')->where('published_at', '<=', now());
        }
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($id);
    }

    public function visible(Model $item): bool
    {
        return ! ($item instanceof Article) || ($item->published_at !== null && $item->published_at->lte(now()));
    }

    public function summary(Model $item): ?array
    {
        if (! $this->visible($item)) {
            return null;
        }

        return [
            'item_type' => $item->getMorphClass(), 'item_id' => $item->getKey(),
            'title' => $item instanceof Media ? $item->content?->title : ($item->title ?? $item->name),
            'thumbnail_url' => $item instanceof Media ? $item->content?->thumbnail_url
                : ($item->thumbnail_url ?? $item->cover_image_url ?? $item->image_url),
            'share_url' => route('member.catalog.show', ['type' => $item->getMorphClass(), 'id' => $item->getKey()]),
        ];
    }

    public function data(Model $item, string $routeName = 'member.catalog.show'): ?array
    {
        if (! $this->visible($item)) {
            return null;
        }
        $fields = [
            $item->getKeyName(), 'category_id', 'content_id', 'title', 'name', 'type', 'genre',
            'fandom_name', 'description', 'bio', 'thumbnail_url', 'image_url', 'cover_image_url',
            'release_date', 'popularity_score', 'view_count', 'is_featured', 'published_at',
            'created_at', 'updated_at', 'tag', 'is_upcoming', 'media_type', 'media_url', 'duration_seconds',
        ];
        $data = array_intersect_key($item->toArray(), array_flip($fields));
        $data['item_type'] = $item->getMorphClass();
        $data['item_id'] = $item->getKey();
        $data['share_url'] = route($routeName, ['type' => $item->getMorphClass(), 'id' => $item->getKey()]);
        if (! ($item instanceof Media)) {
            $data['category'] = $item->category?->only(['category_id', 'name', 'slug']);
        }
        if ($item instanceof Article) {
            $data['body_html'] = app(HtmlSanitizer::class)->clean($item->body_html);
            $data['author'] = $item->author?->only(['name']);
            $data['images'] = $item->images->toArray();
            $data['timeline'] = $item->timeline->toArray();
        }
        if ($item instanceof Content) {
            $data['tags'] = $item->tags->toArray();
            $data['media'] = $item->media->map(fn ($media) => $media->only(['media_id', 'media_type', 'media_url', 'duration_seconds']))->all();
        }
        if ($item instanceof Media) {
            $data['content'] = $item->content?->only(['content_id', 'category_id', 'title', 'genre', 'fandom_name', 'thumbnail_url']);
            $data['tags'] = $item->content?->tags->toArray() ?? [];
            $data['rating'] = DB::table('vw_media_average_rating')->where('media_id', $item->getKey())->first()
                ?? ['avg_rating' => null, 'thumbs_up' => 0, 'thumbs_down' => 0, 'total_ratings' => 0];
        }
        if ($item instanceof MerchandiseItem) {
            $data['images'] = $item->images->toArray();
        }

        return $data;
    }

    public function explorer(array $filters)
    {
        // A consistent projection enables one database-side search over distinct resources.
        // Content with attached media is represented by its playable media entries.
        $content = DB::table('content as c')->selectRaw(
            "'content' AS item_type, c.content_id AS item_id, c.category_id, c.title, c.type AS content_type,
            c.genre, c.fandom_name, c.description, c.thumbnail_url, c.release_date, c.popularity_score,
            c.created_at, 0 AS is_featured, 0 AS is_upcoming"
        )->whereNotExists(fn ($q) => $q->selectRaw('1')->from('media')->whereColumn('media.content_id', 'c.content_id'));
        $articles = DB::table('articles as a')->selectRaw(
            "'article' AS item_type, a.article_id AS item_id, a.category_id, a.title, 'article' AS content_type,
            a.genre, a.fandom_name, NULL AS description, a.cover_image_url AS thumbnail_url, a.release_date,
            a.popularity_score, a.published_at AS created_at, a.is_featured, 0 AS is_upcoming"
        )->whereNotNull('a.published_at')->where('a.published_at', '<=', now());
        $characters = DB::table('character_profiles as c')->selectRaw(
            "'character' AS item_type, c.character_id AS item_id, c.category_id, c.name AS title, 'character' AS content_type,
            c.genre, c.fandom_name, c.bio AS description, c.image_url AS thumbnail_url, c.release_date,
            c.popularity_score, c.created_at, 0 AS is_featured, 0 AS is_upcoming"
        );
        $media = DB::table('media as m')->join('content as c', 'c.content_id', '=', 'm.content_id')->selectRaw(
            "'media' AS item_type, m.media_id AS item_id, c.category_id, c.title, m.media_type AS content_type,
            c.genre, c.fandom_name, c.description, c.thumbnail_url, c.release_date, c.popularity_score,
            m.created_at, 0 AS is_featured, 0 AS is_upcoming"
        );
        $merchandise = DB::table('merchandise_items as m')->selectRaw(
            "'merchandise' AS item_type, m.item_id, m.category_id, m.name AS title, 'merchandise' AS content_type,
            NULL AS genre, m.fandom_name, m.description, m.image_url AS thumbnail_url, m.release_date,
            m.view_count AS popularity_score, m.created_at, 0 AS is_featured, m.is_upcoming"
        );
        $query = DB::query()->fromSub($content->unionAll($articles)->unionAll($characters)->unionAll($media)->unionAll($merchandise), 'catalog');
        foreach (['category_id', 'genre', 'fandom_name', 'content_type', 'item_type', 'is_featured'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['q'])) {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$filters['q'].'%')
                ->orWhere('description', 'like', '%'.$filters['q'].'%')->orWhere('fandom_name', 'like', '%'.$filters['q'].'%'));
        }
        if (isset($filters['release_year'])) {
            $query->whereBetween('release_date', [$filters['release_year'].'-01-01', $filters['release_year'].'-12-31']);
        }
        if (isset($filters['min_popularity'])) {
            $query->where('popularity_score', '>=', $filters['min_popularity']);
        }
        if (! empty($filters['upcoming'])) {
            $query->where(fn ($q) => $q->where('release_date', '>=', today()->toDateString())
                ->orWhere(fn ($q) => $q->where('is_upcoming', true)->whereNull('release_date')));
        }
        if (isset($filters['tag_id'])) {
            $tag = $filters['tag_id'];
            $query->where(function ($q) use ($tag) {
                $q->where(fn ($q) => $q->where('item_type', 'content')->whereIn('item_id', DB::table('content_tags')->select('content_id')->where('tag_id', $tag)))
                    ->orWhere(fn ($q) => $q->where('item_type', 'media')->whereIn('item_id',
                        DB::table('media')->join('content_tags', 'content_tags.content_id', '=', 'media.content_id')->select('media_id')->where('tag_id', $tag)));
            });
        }
        if (isset($filters['merchandise_tag'])) {
            $query->where('item_type', 'merchandise')->whereIn('item_id', DB::table('merchandise_items')->select('item_id')->where('tag', $filters['merchandise_tag']));
        }
        [$sort, $direction] = match ($filters['sort'] ?? 'latest') {
            'popular' => ['popularity_score', 'desc'], 'alphabetical' => ['title', 'asc'],
            'release_date' => ['release_date', 'asc'], default => ['created_at', 'desc'],
        };

        return $query->orderBy($sort, $direction)->orderBy('item_type')->orderBy('item_id');
    }
}
