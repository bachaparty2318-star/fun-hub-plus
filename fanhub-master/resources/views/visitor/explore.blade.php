@extends('visitor.layout')
@section('title', 'Explore - Fan Hub Plus')
@php
    $img = fn($u) => $u ? str_replace('/storage/', '/media/', $u) : asset('assets/images/hero section/hero-anime.jfif');
    $url = fn($i) => $i->item_type === 'character' ? url('/character/'.$i->item_id) : ($i->item_type === 'article' ? url('/article/'.$i->item_id) : ($i->item_type === 'merchandise' ? url('/merchandise/'.$i->item_id) : url('/content/'.$i->item_type.'/'.$i->item_id)));
@endphp
@section('content')
<div class="shell explore-page">
    <nav class="crumbs"><a href="{{ url('/') }}">Home</a><span>›</span><span>Explore</span></nav>
    <section class="hero-card">
        <p class="kicker">Universal search</p>
        <h1>Search every fandom table</h1>
        <p class="lead">Mixed results from content, articles, characters, multimedia and merchandise using the existing Fan Hub Plus backend filters.</p>
    </section>
    <form class="filter-bar" method="get">
        <div class="field"><label>Search</label><input name="q" value="{{ request('q') }}" placeholder="Search titles, descriptions, fandoms"></div>
        <div class="field"><label>Category</label><select name="category_id"><option value="">All</option>@foreach($categories as $c)<option value="{{ $c->category_id }}" @selected((string)request('category_id')===(string)$c->category_id)>{{ $c->name }}</option>@endforeach</select></div>
        <div class="field"><label>Genre</label><input name="genre" value="{{ request('genre') }}" placeholder="Action"></div>
        <div class="field"><label>Type</label><select name="content_type"><option value="">All</option>@foreach(['article','video','trailer','character','merchandise'] as $t)<option value="{{ $t }}" @selected(request('content_type')===$t)>{{ ucfirst($t) }}</option>@endforeach</select></div>
        <div class="field"><label>Year</label><input name="release_year" value="{{ request('release_year') }}" placeholder="2026"></div>
        <div class="field"><label>Popularity</label><input name="min_popularity" value="{{ request('min_popularity') }}" placeholder="Min score"></div>
        <div class="field"><label>Sort</label><select name="sort"><option value="latest" @selected(request('sort','latest')==='latest')>Latest</option><option value="popular" @selected(request('sort')==='popular')>Popular</option><option value="alphabetical" @selected(request('sort')==='alphabetical')>A-Z</option><option value="release_date" @selected(request('sort')==='release_date')>Release date</option></select></div>
        <button class="btn" type="submit">Search</button>
    </form>
    <section class="grid explore-results">
        @forelse($items as $item)
            <a class="content-card" href="{{ $url($item) }}"><article><div class="card-media"><img src="{{ $img($item->thumbnail_url) }}" alt="{{ $item->title }}"></div><div class="card-body"><span class="pill">{{ str_replace('_',' ',$item->item_type) }}</span><h2>{{ $item->title }}</h2><p class="muted">{{ \Illuminate\Support\Str::limit(strip_tags($item->description ?: $item->fandom_name ?: 'Fan Hub Plus result'), 110) }}</p><div class="meta"><span>{{ $item->fandom_name ?: 'Fan Hub Plus' }}</span><span>{{ $item->popularity_score ?: 0 }} pts</span></div></div></article></a>
        @empty
            <article class="article-panel"><h2>No results found</h2><p class="muted">Try a broader search or remove one filter.</p></article>
        @endforelse
    </section>
    @if($items->hasPages())
        <nav class="visitor-pager" aria-label="Explore pagination">
            <div class="pager-summary">
                Showing {{ $items->firstItem() }}-{{ $items->lastItem() }} of {{ $items->total() }} results
            </div>
            <div class="pager-actions">
                @if($items->onFirstPage())
                    <span class="pager-btn is-disabled">Previous</span>
                @else
                    <a class="pager-btn" href="{{ $items->previousPageUrl() }}" rel="prev">Previous</a>
                @endif

                @foreach($items->getUrlRange(1, $items->lastPage()) as $page => $link)
                    @if($page === $items->currentPage())
                        <span class="pager-btn is-active">{{ $page }}</span>
                    @elseif($page === 1 || $page === $items->lastPage() || abs($page - $items->currentPage()) <= 1)
                        <a class="pager-btn" href="{{ $link }}">{{ $page }}</a>
                    @elseif(abs($page - $items->currentPage()) === 2)
                        <span class="pager-dots">...</span>
                    @endif
                @endforeach

                @if($items->hasMorePages())
                    <a class="pager-btn" href="{{ $items->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="pager-btn is-disabled">Next</span>
                @endif
            </div>
        </nav>
    @endif
</div>
@endsection
