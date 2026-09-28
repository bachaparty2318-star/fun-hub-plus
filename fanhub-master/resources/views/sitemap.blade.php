@extends('visitor.layout')
@section('title', 'Sitemap - Fan Hub Plus')
@php $groups = [
    'Visitor' => ['Homepage'=>'/','Explore'=>'/explore','Characters'=>'/characters','Articles'=>'/articles','Events'=>'/events'],
    'Categories' => ['Anime'=>'/category/anime','Gaming'=>'/category/gaming','Movies'=>'/category/movies','TV Shows'=>'/category/tv-shows','K-Pop'=>'/category/k-pop','Comics'=>'/category/comics','Manga'=>'/category/manga','Cosplay'=>'/category/cosplay'],
    'Showcases' => ['Merchandise'=>'/merchandise','Sitemap'=>'/sitemap','Feedback'=>'/feedback','Submissions'=>'/submissions'],
    'Account' => ['Member Login'=>'/user/login','Register'=>'/register','Forgot Password'=>'/forgot-password','Admin Login'=>'/admin/login'],
]; @endphp
@section('content')
<main class="shell">
    <nav class="crumbs"><a href="{{ url('/') }}">Home</a><span>›</span><span>Sitemap</span></nav>
    <section class="hero-card"><p class="kicker">Project map</p><h1>Sitemap</h1><p class="lead">Quick navigation for the Fan Hub Plus visitor, member and admin areas.</p></section>
    <section class="grid section">
        @foreach($groups as $title => $links)
            <article class="content-card"><div class="card-body"><span class="pill">{{ $title }}</span><h2>{{ $title }}</h2><div class="tags">@foreach($links as $label => $href)<a class="btn ghost" href="{{ url($href) }}">{{ $label }}</a>@endforeach</div></div></article>
        @endforeach
    </section>
</main>
@endsection
