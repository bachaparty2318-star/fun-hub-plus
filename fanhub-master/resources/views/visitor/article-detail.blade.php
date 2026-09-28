@extends('visitor.layout')
@section('title', $article->title.' - Fan Hub Plus')
@php $img = fn($u) => $u ? str_replace('/storage/', '/media/', $u) : asset('assets/images/Content/Demon Slayer Season 4 Trailer.jfif'); @endphp
@section('content')
<main class="shell">
    <nav class="crumbs"><a href="{{ url('/') }}">Home</a><span>›</span><a href="{{ url('/articles') }}">Articles</a><span>›</span>@if($article->category)<a href="{{ url('/category/'.$article->category->slug) }}">{{ $article->category->name }}</a><span>›</span>@endif<span>{{ $article->title }}</span></nav>
    <article class="article-panel"><div class="media-cover"><img src="{{ $img($article->cover_image_url) }}" alt="{{ $article->title }}"></div><p class="kicker">{{ $article->fandom_name ?: $article->category?->name }}</p><h1>{{ $article->title }}</h1><div class="tags"><span class="pill">{{ $article->published_at?->format('M d, Y') }}</span>@if($article->category)<span class="pill">{{ $article->category->name }}</span>@endif</div><div class="body-copy">{!! $article->body_html !!}</div>
        @if($article->images->count())<div class="gallery">@foreach($article->images as $image)<img src="{{ $img($image->image_url) }}" alt="{{ $image->caption ?: $article->title }}">@endforeach</div>@endif
        @if($article->timeline->count())<div class="timeline">@foreach($article->timeline as $event)<article class="timeline-item"><strong>{{ $event->event_label }}</strong><p class="muted">{{ $event->event_date?->format('M d, Y') }} · {{ $event->description }}</p></article>@endforeach</div>@endif
    </article>
    <section class="section compact"><div class="section-head"><div><p class="kicker">Related articles</p><h2>Keep reading</h2></div></div><div class="related-row">@foreach($related as $item)<a class="mini-card" href="{{ url('/article/'.$item->article_id) }}"><img src="{{ $img($item->cover_image_url) }}" alt="{{ $item->title }}"><h3>{{ $item->title }}</h3></a>@endforeach</div></section>
</main>
@endsection
