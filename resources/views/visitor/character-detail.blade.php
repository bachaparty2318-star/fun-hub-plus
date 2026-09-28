@extends('visitor.layout')
@section('title', $character->name.' - Fan Hub Plus')
@php
    $image = $character->image_url ? str_replace('/storage/', '/media/', $character->image_url) : asset('assets/images/Character profiles/Tanjiro Kamado.jfif');
    $relatedUrl = fn($item) => $item->item_type === 'character' ? url('/character/'.$item->item_id) : ($item->item_type === 'article' ? url('/article/'.$item->item_id) : url('/content/'.$item->item_type.'/'.$item->item_id));
    $relatedImage = fn($item) => str_replace('/storage/', '/media/', $item->thumbnail_url ?: asset('assets/images/hero section/hero-anime.jfif'));
@endphp
@section('content')
<main class="shell">
    <nav class="crumbs"><a href="{{ url('/') }}">Home</a><span>›</span><a href="{{ url('/characters') }}">Characters</a><span>›</span><span>{{ $character->name }}</span></nav>
    <section class="hero-card with-media"><div><p class="kicker">{{ $character->fandom_name ?: $character->category?->name ?: 'Character' }}</p><h1>{{ $character->name }}</h1><p class="lead">{{ $character->bio ?: 'A Fan Hub Plus character profile with fandom details, category context and related content.' }}</p><div class="tags">@if($character->category)<span class="pill">{{ $character->category->name }}</span>@endif @if($character->genre)<span class="pill">{{ $character->genre }}</span>@endif @if($character->popularity_score)<span class="pill">{{ $character->popularity_score }} pts</span>@endif</div></div><div class="character-photo"><img src="{{ $image }}" alt="{{ $character->name }}"></div></section>
    <section class="section compact"><div class="section-head"><div><p class="kicker">Related content</p><h2>More from {{ $character->category?->name ?? 'this fandom' }}</h2></div></div><div class="related-row">@foreach($related as $item)<a class="mini-card" href="{{ $relatedUrl($item) }}"><img src="{{ $relatedImage($item) }}" alt="{{ $item->title }}"><h3>{{ $item->title }}</h3></a>@endforeach</div></section>
</main>
@endsection
