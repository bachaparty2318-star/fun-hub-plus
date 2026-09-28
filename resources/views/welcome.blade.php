@extends('visitor.layout')

@section('title', 'Fan Hub Plus - Premium Fandom Discovery')

@php
    $img = fn($url, $fallback = 'assets/images/hero section/hero-anime.jfif') => $url ? str_replace('/storage/', '/media/', $url) : asset($fallback);
    $catalogUrl = function ($item) {
        return match ($item->item_type) {
            'character' => url('/character/'.$item->item_id),
            'article' => url('/article/'.$item->item_id),
            'merchandise' => url('/merchandise/'.$item->item_id),
            default => url('/content/'.$item->item_type.'/'.$item->item_id),
        };
    };
    $categoryFallback = [
        'anime' => 'assets/images/hero section/hero-anime.jfif',
        'gaming' => 'assets/images/hero section/hero-gaming.jfif',
        'movies' => 'assets/images/hero section/hero-movies.jfif',
        'tv-shows' => 'assets/images/hero section/hero-tv.jfif',
        'k-pop' => 'assets/images/hero section/hero-kpop.jfif',
        'comics' => 'assets/images/hero section/hero-comics.jfif',
        'manga' => 'assets/images/Content/One Piece Chapter 1120 Review.jfif',
        'cosplay' => 'assets/images/hero section/hero-cosplay.jfif',
    ];
@endphp

@section('content')
<section class="home-hero wide-shell">
    <div class="home-hero-panel">
        <div>
            <p class="kicker">Your universe. All your fandoms.</p>
            <h1>One hub. Every fandom. Endless worlds to explore.</h1>
            <p class="lead">Browse dynamic Fan Hub Plus content from the admin-managed catalog: categories, releases, articles, characters, media, events and display-only merchandise.</p>
            <div class="hero-actions">
                <a class="btn alt" href="{{ url('/explore') }}">Explore Fandoms <i class="fi fi-rr-arrow-right"></i></a>
                <a class="btn ghost" href="{{ url('/register') }}">Join Fan Hub</a>
            </div>
        </div>
        <div class="hero-collage" aria-label="Featured fandom artwork">
            @foreach($featured->take(3) as $item)
                <a class="hero-tile" href="{{ $catalogUrl($item) }}">
                    <img src="{{ $img($item->thumbnail_url) }}" alt="{{ $item->title }}">
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="section wide-shell" id="categories">
    <div class="section-head">
        <div><p class="kicker">Explore your universe</p><h2>Eight fandom gateways</h2></div>
        <a class="btn ghost" href="{{ url('/explore') }}">Search all</a>
    </div>
    <div class="grid">
        @foreach($categories->take(8) as $category)
            <a class="content-card category-card" href="{{ url('/category/'.$category->slug) }}">
                <article>
                    <div class="card-media"><img src="{{ $img($category->icon_url, $categoryFallback[$category->slug] ?? 'assets/images/hero section/hero-anime.jfif') }}" alt="{{ $category->name }}"></div>
                    <div class="card-body">
                        <span class="pill">{{ $category->name }}</span>
                        <h3>{{ $category->name }}</h3>
                        <p class="muted">{{ $category->description ?: 'Explore stories, media and fan discoveries from this category.' }}</p>
                    </div>
                </article>
            </a>
        @endforeach
    </div>
</section>

<section class="section shell">
    <div class="section-head"><div><p class="kicker">Popular now</p><h2>Trending content</h2></div></div>
    <div class="bento-grid">
        @foreach($featured->take(5) as $item)
            <a class="content-card {{ $loop->first ? 'feature-card' : '' }}" href="{{ $catalogUrl($item) }}">
                <article>
                    <div class="card-media"><img src="{{ $img($item->thumbnail_url) }}" alt="{{ $item->title }}"></div>
                    <div class="card-body">
                        <span class="pill">{{ str_replace('_', ' ', $item->content_type ?: $item->item_type) }}</span>
                        <h3>{{ $item->title }}</h3>
                        <p class="muted">{{ \Illuminate\Support\Str::limit(strip_tags($item->description ?: $item->fandom_name ?: 'Fan Hub Plus pick'), 105) }}</p>
                        <div class="meta"><span>{{ $item->fandom_name ?: 'Fan Hub Plus' }}</span><span>{{ $item->popularity_score ?: 0 }} pts</span></div>
                    </div>
                </article>
            </a>
        @endforeach
    </div>
</section>

<section class="section wide-shell">
    <div class="section-head"><div><p class="kicker">Fresh drops</p><h2>Latest and new releases</h2></div><a class="btn ghost" href="{{ url('/explore?sort=latest') }}">View latest</a></div>
    <div class="rail-row">
        @foreach($latest as $item)
            <a class="content-card" href="{{ $catalogUrl($item) }}">
                <article><div class="card-media"><img src="{{ $img($item->thumbnail_url) }}" alt="{{ $item->title }}"></div><div class="card-body"><span class="pill">{{ $item->item_type }}</span><h3>{{ $item->title }}</h3><p class="muted">{{ \Illuminate\Support\Str::limit(strip_tags($item->description ?: $item->fandom_name), 95) }}</p></div></article>
            </a>
        @endforeach
    </div>
</section>

<section class="section shell">
    <div class="section-head"><div><p class="kicker">Editorial</p><h2>Featured articles</h2></div><a class="btn ghost" href="{{ url('/articles') }}">Read articles</a></div>
    <div class="bento-grid">
        @foreach($articles as $article)
            <a class="content-card {{ $loop->first ? 'feature-card' : '' }}" href="{{ url('/article/'.$article->article_id) }}">
                <article><div class="card-media"><img src="{{ $img($article->cover_image_url, 'assets/images/Content/Demon Slayer Season 4 Trailer.jfif') }}" alt="{{ $article->title }}"></div><div class="card-body"><span class="pill">{{ $article->category?->name ?? 'Article' }}</span><h3>{{ $article->title }}</h3><p class="muted">{{ $article->published_at?->format('M d, Y') }} · {{ $article->fandom_name }}</p></div></article>
            </a>
        @endforeach
    </div>
</section>

<section class="section wide-shell">
    <div class="section-head"><div><p class="kicker">Character spotlight</p><h2>Profiles fans revisit</h2></div><a class="btn ghost" href="{{ url('/characters') }}">All characters</a></div>
    <div class="rail-row">
        @foreach($characters as $character)
            <a class="content-card" href="{{ url('/character/'.$character->character_id) }}">
                <article><div class="card-media character-photo"><img src="{{ $img($character->image_url, 'assets/images/Character profiles/Tanjiro Kamado.jfif') }}" alt="{{ $character->name }}"></div><div class="card-body"><span class="pill">{{ $character->category?->name ?? 'Character' }}</span><h3>{{ $character->name }}</h3><p class="muted">{{ \Illuminate\Support\Str::limit($character->bio, 95) }}</p></div></article>
            </a>
        @endforeach
    </div>
</section>

<section class="section shell">
    <div class="section-head"><div><p class="kicker">Watch and preview</p><h2>Multimedia</h2></div></div>
    <div class="grid">
        @foreach($media as $item)
            <a class="content-card" href="{{ url('/content/media/'.$item->media_id) }}">
                <article><div class="card-media"><img src="{{ $img($item->content?->thumbnail_url) }}" alt="{{ $item->content?->title }}"></div><div class="card-body"><span class="pill">{{ $item->media_type }}</span><h3>{{ $item->content?->title ?? 'Media preview' }}</h3><p class="muted">{{ $item->content?->fandom_name ?: 'Fan Hub Plus media' }}</p></div></article>
            </a>
        @endforeach
    </div>
</section>

<section class="section wide-shell">
    <div class="section-head"><div><p class="kicker">Display-only showcase</p><h2>Merchandise</h2></div><a class="btn ghost" href="{{ url('/merchandise') }}">View showcase</a></div>
    <div class="rail-row">
        @foreach($merchandise as $item)
            <a class="content-card" href="{{ url('/merchandise/'.$item->item_id) }}">
                <article><div class="card-media"><img src="{{ $img($item->image_url, 'assets/images/Merchandise/Spider-Man Hoodie.jfif') }}" alt="{{ $item->name }}"></div><div class="card-body"><span class="pill">{{ $item->tag ?: 'Merch' }}</span><h3>{{ $item->name }}</h3><p class="muted">{{ $item->fandom_name ?: $item->category?->name }}</p></div></article>
            </a>
        @endforeach
    </div>
</section>

<section class="section shell">
    <div class="section-head"><div><p class="kicker">Fan calendar</p><h2>Upcoming events</h2></div><a class="btn ghost" href="{{ url('/events') }}">Open events</a></div>
    <div class="grid">
        @foreach($events as $event)
            <article class="content-card"><div class="card-body"><span class="pill">{{ $event->event_type ?: 'Event' }}</span><h3>{{ $event->title }}</h3><p class="muted">{{ $event->city }} · {{ $event->event_date?->format('M d, Y h:i A') }}</p>@if($event->ticket_link)<a class="btn alt" href="{{ $event->ticket_link }}">Ticket link</a>@endif</div></article>
        @endforeach
    </div>
</section>

<section class="section shell">
    <div class="hero-card">
        <p class="kicker">Project map</p>
        <h2 class="section-title">Need the full structure?</h2>
        <p class="lead">Open the visual sitemap for every visitor, category, account and resource link in one place.</p>
        <div class="hero-actions"><a class="btn" href="{{ url('/sitemap') }}">Open Sitemap</a><a class="btn alt" href="{{ url('/register') }}">Join Fan Hub</a></div>
    </div>
</section>
@endsection
