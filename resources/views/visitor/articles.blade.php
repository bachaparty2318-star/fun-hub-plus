@extends('visitor.layout')
@section('title', 'Articles - Fan Hub Plus')
@php $img = fn($u) => $u ? str_replace('/storage/', '/media/', $u) : asset('assets/images/Content/Demon Slayer Season 4 Trailer.jfif'); @endphp
@section('content')
<main class="shell">
    <nav class="crumbs"><a href="{{ url('/') }}">Home</a><span>›</span><span>Articles</span></nav>
    <section class="hero-card"><p class="kicker">Articles hub</p><h1>Featured stories first</h1><p class="lead">Read editorial fandom stories, reviews, timelines and galleries from the Fan Hub Plus database.</p></section>
    <form class="filter-bar" method="get"><div class="field"><label>Category</label><select name="category_id"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c->category_id }}" @selected((string)request('category_id')===(string)$c->category_id)>{{ $c->name }}</option>@endforeach</select></div><button class="btn" type="submit">Filter</button></form>
    @if($featured->count())
        <section class="bento-grid compact">@foreach($featured as $article)<a class="content-card {{ $loop->first ? 'feature-card' : '' }}" href="{{ url('/article/'.$article->article_id) }}"><article><div class="card-media"><img src="{{ $img($article->cover_image_url) }}" alt="{{ $article->title }}"></div><div class="card-body"><span class="pill">Featured</span><h2>{{ $article->title }}</h2><p class="muted">{{ $article->fandom_name ?: $article->category?->name }}</p></div></article></a>@endforeach</section>
    @endif
    <section class="grid section compact">@foreach($articles as $article)<a class="content-card" href="{{ url('/article/'.$article->article_id) }}"><article><div class="card-media"><img src="{{ $img($article->cover_image_url) }}" alt="{{ $article->title }}"></div><div class="card-body"><span class="pill">{{ $article->category?->name ?? 'Article' }}</span><h2>{{ $article->title }}</h2><p class="muted">{{ $article->published_at?->format('M d, Y') ?? 'Published story' }}</p></div></article></a>@endforeach</section>
    <div class="pagination">{{ $articles->links() }}</div>
</main>
@endsection
