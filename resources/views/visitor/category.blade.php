@extends('visitor.layout')
@section('title', $category->name.' - Fan Hub Plus')
@php
    $assetUrl = fn($url) => $url ? str_replace('/storage/', '/media/', $url) : asset('assets/images/hero section/hero-anime.jfif');
    $detailUrl = fn($item) => $item->item_type === 'character' ? url('/character/'.$item->item_id) : ($item->item_type === 'article' ? url('/article/'.$item->item_id) : ($item->item_type === 'merchandise' ? url('/merchandise/'.$item->item_id) : url('/content/'.$item->item_type.'/'.$item->item_id)));
@endphp
@section('content')
<main class="shell">
    <nav class="crumbs"><a href="{{ url('/') }}">Home</a><span>›</span><span>{{ $category->name }}</span></nav>
    <section class="hero-card with-media"><div><p class="kicker">Category page</p><h1>{{ $category->name }}</h1><p class="lead">{{ $category->description ?: 'Explore content, characters, articles, trailers, merchandise and fan updates in this fandom category.' }}</p></div><div class="hero-icon"><img src="{{ $assetUrl($category->icon_url) }}" alt="{{ $category->name }}"></div></section>
    <form class="filter-bar" method="get">
        <div class="field"><label>Genre</label><select name="genre"><option value="">All genres</option>@foreach($genres as $genre)<option value="{{ $genre }}" @selected(request('genre')===$genre)>{{ $genre }}</option>@endforeach</select></div>
        <div class="field"><label>Type</label><select name="content_type"><option value="">All types</option>@foreach(['article'=>'Articles','video'=>'Videos','trailer'=>'Trailers','character'=>'Characters','merchandise'=>'Merchandise'] as $value=>$label)<option value="{{ $value }}" @selected(request('content_type')===$value)>{{ $label }}</option>@endforeach</select></div>
        <div class="field"><label>Popularity</label><input name="min_popularity" value="{{ request('min_popularity') }}" placeholder="Min score"></div>
        <div class="field"><label>Sort</label><select name="sort"><option value="latest" @selected(request('sort','latest')==='latest')>Latest</option><option value="popular" @selected(request('sort')==='popular')>Popular</option><option value="alphabetical" @selected(request('sort')==='alphabetical')>A-Z</option><option value="release_date" @selected(request('sort')==='release_date')>Release date</option></select></div>
        <button class="btn" type="submit">Apply</button>
    </form>
    <section class="grid">
        @forelse($items as $item)
            <a class="content-card" href="{{ $detailUrl($item) }}"><article><div class="card-media"><img src="{{ $assetUrl($item->thumbnail_url) }}" alt="{{ $item->title }}"></div><div class="card-body"><span class="pill">{{ str_replace('_',' ',$item->content_type ?: $item->item_type) }}</span><h2>{{ $item->title }}</h2><p class="muted">{{ \Illuminate\Support\Str::limit(strip_tags($item->description ?: 'Open this Fan Hub Plus item for details.'), 120) }}</p><div class="meta"><span>{{ $item->fandom_name ?: $category->name }}</span><span>{{ $item->popularity_score ?: 0 }} pts</span></div></div></article></a>
        @empty
            <article class="article-panel"><h2>No content yet</h2><p class="muted">Add content from admin and it will appear here.</p></article>
        @endforelse
    </section>
    <div class="pagination">{{ $items->links() }}</div>
</main>
@endsection
