<?php

namespace App\Services;

use App\Models;
use App\Rules\SafeUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class AdminResources
{
    public function all(?Model $record = null): array
    {
        $url = ['nullable', 'string', 'max:500', new SafeUrl];
        $category = ['required', 'integer', 'exists:categories,category_id'];
        $short = ['nullable', 'string', 'max:500'];
        $fandom = ['nullable', 'string', 'max:150'];
        $filters = [
            'genre' => 'nullable|string|max:100',
            'release_date' => 'nullable|date_format:Y-m-d',
            'popularity_score' => 'sometimes|integer|min:0|max:4294967295',
        ];
        $unique = fn ($table, $column, $key) => Rule::unique($table, $column)->ignore($record?->getKey(), $key);
        $definition = fn ($model, $rules, $search, $filter = [], $with = [], $sort = [], $creator = null) => compact('model', 'rules', 'search', 'filter', 'with', 'sort', 'creator');

        return [
            'categories' => $definition(Models\Category::class, [
                'name' => ['required', 'string', 'max:50', $unique('categories', 'name', 'category_id')],
                'slug' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $unique('categories', 'slug', 'category_id')],
                'description' => $short, 'icon_url' => $url,
            ], ['name', 'description'], [], [], ['name', 'created_at']),
            'tags' => $definition(Models\Tag::class, [
                'tag_name' => ['required', 'string', 'max:50', $unique('tags', 'tag_name', 'tag_id')],
            ], ['tag_name'], [], [], ['tag_name']),
            'content' => $definition(Models\Content::class, array_merge([
                'category_id' => $category, 'title' => 'required|string|max:200',
                'type' => 'required|in:article,video,audio,image,trailer,animated_explainer,release',
                'description' => 'nullable|string|max:50000', 'thumbnail_url' => $url,
                'fandom_name' => $fandom, 'tag_ids' => 'sometimes|array|max:50',
                'tag_ids.*' => 'integer|distinct|exists:tags,tag_id',
            ], $filters), ['title', 'description', 'fandom_name'], ['category_id', 'type', 'genre', 'fandom_name'], ['category', 'tags'], ['title', 'created_at', 'release_date', 'popularity_score'], 'created_by'),
            'media' => $definition(Models\Media::class, [
                'content_id' => 'required|integer|exists:content,content_id',
                'media_type' => 'required|in:video,audio,trailer,animated_explainer,image',
                'media_url' => ['required', 'string', 'max:500', new SafeUrl],
                'duration_seconds' => 'nullable|integer|min:0|max:4294967295',
            ], ['media_url'], ['content_id', 'media_type'], ['content.tags'], ['created_at'], 'uploaded_by'),
            'characters' => $definition(Models\CharacterProfile::class, array_merge([
                'category_id' => $category, 'name' => 'required|string|max:150',
                'fandom_name' => $fandom, 'bio' => 'nullable|string|max:50000', 'image_url' => $url,
            ], $filters), ['name', 'bio', 'fandom_name'], ['category_id', 'genre', 'fandom_name'], ['category'], ['name', 'created_at', 'release_date', 'popularity_score'], 'created_by'),
            'articles' => $definition(Models\Article::class, array_merge([
                'category_id' => $category, 'title' => 'required|string|max:200',
                'body_html' => 'required|string|max:200000', 'cover_image_url' => $url,
                'fandom_name' => $fandom, 'is_featured' => 'sometimes|boolean',
                'published_at' => 'nullable|date',
            ], $filters), ['title', 'fandom_name'], ['category_id', 'genre', 'fandom_name', 'is_featured'], ['category', 'author:user_id,name', 'images', 'timeline'], ['title', 'created_at', 'published_at', 'release_date', 'popularity_score'], 'author_id'),
            'article-images' => $definition(Models\ArticleImage::class, [
                'article_id' => 'required|integer|exists:articles,article_id',
                'image_url' => ['required', 'string', 'max:500', new SafeUrl],
                'caption' => 'nullable|string|max:255', 'display_order' => 'sometimes|integer|min:0|max:65535',
            ], ['caption'], ['article_id'], [], ['display_order']),
            'article-timeline-events' => $definition(Models\ArticleTimelineEvent::class, [
                'article_id' => 'required|integer|exists:articles,article_id',
                'event_label' => 'required|string|max:150', 'event_date' => 'nullable|date_format:Y-m-d',
                'description' => $short, 'display_order' => 'sometimes|integer|min:0|max:65535',
            ], ['event_label', 'description'], ['article_id'], [], ['display_order', 'event_date']),
            'merchandise' => $definition(Models\MerchandiseItem::class, [
                'category_id' => $category, 'name' => 'required|string|max:200',
                'description' => $short, 'image_url' => $url, 'fandom_name' => $fandom,
                'tag' => ['sometimes', Rule::in(['Limited Edition', 'Pre-Order', 'Collectible', 'Standard'])],
                'is_upcoming' => 'sometimes|boolean', 'release_date' => 'nullable|date_format:Y-m-d',
            ], ['name', 'description', 'fandom_name'], ['category_id', 'tag', 'is_upcoming', 'fandom_name'], ['category', 'images'], ['name', 'created_at', 'release_date']),
            'merchandise-images' => $definition(Models\MerchandiseImage::class, [
                'item_id' => 'required|integer|exists:merchandise_items,item_id',
                'image_url' => ['required', 'string', 'max:500', new SafeUrl],
                'display_order' => 'sometimes|integer|min:0|max:65535',
            ], [], ['item_id'], [], ['display_order']),
            'events' => $definition(Models\Event::class, [
                'category_id' => 'nullable|integer|exists:categories,category_id',
                'title' => 'required|string|max:200', 'description' => 'nullable|string|max:1000',
                'event_type' => 'required|in:convention,premiere,release,meetup,screening',
                'event_date' => 'required|date', 'end_date' => 'nullable|date',
                'city' => 'required|string|max:100', 'venue' => 'nullable|string|max:200',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180', 'ticket_link' => $url,
            ], ['title', 'description', 'city'], ['category_id', 'event_type', 'city'], ['category'], ['title', 'created_at', 'event_date'], 'created_by'),
        ];
    }

    public function get(string $resource, ?Model $record = null): array
    {
        $definition = $this->all($record)[$resource] ?? null;
        abort_unless($definition, 404, 'Unknown resource.');

        return $definition;
    }
}
