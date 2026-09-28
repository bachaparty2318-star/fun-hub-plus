@extends('visitor.layout')
@section('title', 'Merchandise - Fan Hub Plus')
@php $img = fn($u) => $u ? str_replace('/storage/', '/media/', $u) : asset('assets/images/Merchandise/Spider-Man Hoodie.jfif'); @endphp
@section('content')
<main class="shell">
    <nav class="crumbs"><a href="{{ url('/') }}">Home</a><span>›</span><span>Merchandise</span></nav>
    <section class="hero-card"><p class="kicker">Display-only showcase</p><h1>Fan gear and collectibles</h1><p class="lead">Merchandise is for discovery and information only. No cart, checkout, payment or buying flow is included.</p></section>
    <form class="filter-bar" method="get"><div class="field"><label>Tag</label><select name="tag"><option value="">All tags</option>@foreach(['Limited Edition','Pre-Order','Collectible','Standard'] as $tag)<option value="{{ $tag }}" @selected(request('tag')===$tag)>{{ $tag }}</option>@endforeach</select></div><button class="btn" type="submit">Filter</button></form>
    <section class="grid">
        @forelse($items as $item)
            <a class="content-card" href="{{ url('/merchandise/'.$item->item_id) }}"><article><div class="card-media"><img src="{{ $img($item->image_url) }}" alt="{{ $item->name }}"></div><div class="card-body"><span class="pill">{{ $item->tag ?: 'Merch' }}</span><h2>{{ $item->name }}</h2><p class="muted">{{ $item->fandom_name ?: $item->category?->name }}</p></div></article></a>
        @empty
            <article class="article-panel"><h2>No merchandise yet</h2><p class="muted">Add display items from admin and they will appear here.</p></article>
        @endforelse
    </section>
    <div class="pagination">{{ $items->links() }}</div>
</main>
@endsection
