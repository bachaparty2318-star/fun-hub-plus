@extends('visitor.layout')
@php
    $title = $data['title'] ?? $data['name'] ?? 'Fan Hub Plus Content';
    $image = str_replace('/storage/', '/media/', $data['thumbnail_url'] ?? $data['cover_image_url'] ?? $data['image_url'] ?? $data['content']['thumbnail_url'] ?? asset('assets/images/hero section/hero-anime.jfif'));
    $body = $data['body_html'] ?? nl2br(e($data['description'] ?? $data['bio'] ?? 'Explore this Fan Hub Plus detail page.'));
    $tags = collect($data['tags'] ?? [])->map(fn($tag) => $tag['tag_name'] ?? $tag['name'] ?? null)->filter();
    $relatedUrl = fn($item) => $item->item_type === 'character' ? url('/character/'.$item->item_id) : ($item->item_type === 'article' ? url('/article/'.$item->item_id) : ($item->item_type === 'merchandise' ? url('/merchandise/'.$item->item_id) : url('/content/'.$item->item_type.'/'.$item->item_id)));
    $relatedImage = fn($item) => str_replace('/storage/', '/media/', $item->thumbnail_url ?: asset('assets/images/hero section/hero-anime.jfif'));
@endphp
@section('title', $title.' - Fan Hub Plus')
@section('content')
<main class="shell">
    <nav class="crumbs"><a href="{{ url('/') }}">Home</a><span>›</span>@if($category)<a href="{{ url('/category/'.$category->slug) }}">{{ $category->name }}</a><span>›</span>@endif<span>{{ $title }}</span></nav>
    <section class="detail-layout">
        <article class="article-panel">
            <div class="media-cover">
                @if(($data['media_type'] ?? '') === 'video' || str_contains($data['media_url'] ?? '', '.mp4'))
                    <video controls poster="{{ $image }}"><source src="{{ str_replace('/storage/', '/media/', $data['media_url']) }}" type="video/mp4"></video>
                @else
                    <img src="{{ $image }}" alt="{{ $title }} cover">
                @endif
            </div>
            <p class="kicker">{{ $data['fandom_name'] ?? $category?->name ?? 'Fan Hub Plus' }}</p>
            <h1>{{ $title }}</h1>
            <div class="body-copy">{!! $body !!}</div>
            <div class="tags">@forelse($tags as $tag)<span class="pill">#{{ $tag }}</span>@empty<span class="pill">{{ str_replace('_',' ',$data['item_type'] ?? 'content') }}</span>@if(!empty($data['genre']))<span class="pill">{{ $data['genre'] }}</span>@endif @endforelse</div>
        </article>
        <aside class="side-panel">
            <p class="kicker">Member actions</p><h2>Bookmark and rate</h2><p class="muted">Guests can view every detail. Bookmarking and ratings are member features.</p>
            <a class="btn alt" href="{{ url('/register') }}"><i class="fi fi-rr-bookmark"></i> Sign up to unlock</a>
            <a class="btn ghost" style="margin-top:10px" href="{{ url('/user/login') }}"><i class="fi fi-rr-star"></i> Log in</a>
            <div class="tags"><span class="pill">{{ str_replace('_',' ',$data['item_type'] ?? 'content') }}</span>@if(!empty($data['popularity_score']))<span class="pill">{{ $data['popularity_score'] }} pts</span>@endif @if(!empty($data['release_date']))<span class="pill">{{ $data['release_date'] }}</span>@endif</div>
        </aside>
    </section>
    <section class="section compact"><div class="section-head"><div><p class="kicker">Related content</p><h2>More from {{ $category?->name ?? 'this fandom' }}</h2></div></div><div class="related-row">@foreach($related as $item)<a class="mini-card" href="{{ $relatedUrl($item) }}"><img src="{{ $relatedImage($item) }}" alt="{{ $item->title }}"><h3>{{ $item->title }}</h3></a>@endforeach</div></section>
</main>
@endsection
