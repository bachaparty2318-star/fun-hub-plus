@extends('visitor.layout')
@section('title', $item->name.' - Fan Hub Plus')
@php $img = fn($u) => $u ? str_replace('/storage/', '/media/', $u) : asset('assets/images/Merchandise/Spider-Man Hoodie.jfif'); @endphp
@section('content')
<main class="shell">
    <nav class="crumbs"><a href="{{ url('/') }}">Home</a><span>›</span><a href="{{ url('/merchandise') }}">Merchandise</a><span>›</span><span>{{ $item->name }}</span></nav>
    <section class="hero-card with-media"><div><p class="kicker">{{ $item->tag ?: 'Display-only item' }}</p><h1>{{ $item->name }}</h1><p class="lead">{{ $item->description ?: 'Fan Hub Plus merchandise showcase item.' }}</p><div class="tags"><span class="pill">No cart or checkout</span>@if($item->category)<span class="pill">{{ $item->category->name }}</span>@endif @if($item->fandom_name)<span class="pill">{{ $item->fandom_name }}</span>@endif</div></div><div class="hero-icon"><img src="{{ $img($item->image_url) }}" alt="{{ $item->name }}"></div></section>
    @if($item->images->count())<section class="gallery">@foreach($item->images as $image)<img src="{{ $img($image->image_url) }}" alt="{{ $item->name }} gallery image">@endforeach</section>@endif
</main>
@endsection
