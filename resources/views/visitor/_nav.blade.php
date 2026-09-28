@php
    $navCategories = \Illuminate\Support\Facades\Schema::hasTable('categories')
        ? \App\Models\Category::orderBy('category_id')->get(['name', 'slug'])
        : collect();
    $active = fn ($patterns) => request()->is(...(array) $patterns) ? 'is-active' : '';
    $visitorUser = auth()->user();
    $dashboardUrl = $visitorUser?->role === 'admin' ? url('/admin') : url('/user');
@endphp
<header class="visitor-nav-wrap">
    <nav class="visitor-nav" aria-label="Main navigation">
        <a class="brand" href="{{ url('/') }}">
            <span>FanHubPlus</span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="visitorMenu"><i class="fi fi-rr-menu-burger"></i></button>
        <div class="nav-menu" id="visitorMenu">
            <a class="{{ request()->is('/') ? 'is-active' : '' }}" href="{{ url('/') }}">Home</a>
            <a class="{{ $active('explore') }}" href="{{ url('/explore') }}">Explore</a>
            <div class="nav-dropdown">
                <button type="button" class="{{ $active(['category/*', 'visitor/categories/*']) }}">Categories <i class="fi fi-rr-angle-small-down"></i></button>
                <div class="nav-dropdown-panel">
                    @foreach($navCategories as $category)
                        <a href="{{ url('/category/'.$category->slug) }}">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>
            <a class="{{ $active('characters*') }}" href="{{ url('/characters') }}">Characters</a>
            <a class="{{ $active(['articles', 'article/*']) }}" href="{{ url('/articles') }}">Articles</a>
            <a class="{{ $active('merchandise*') }}" href="{{ url('/merchandise') }}">Merchandise</a>
            <a class="{{ $active('events') }}" href="{{ url('/events') }}">Events</a>
            @if($visitorUser)
                <div class="nav-dropdown nav-account">
                    <button type="button" class="account-trigger">
                        <span class="account-avatar">{{ strtoupper(substr($visitorUser->name ?: $visitorUser->email, 0, 1)) }}</span>
                        <span class="account-copy">{{ \Illuminate\Support\Str::limit($visitorUser->name ?: 'Account', 16) }}</span>
                        <i class="fi fi-rr-angle-small-down"></i>
                    </button>
                    <div class="nav-dropdown-panel account-panel">
                        <span class="account-email">{{ $visitorUser->email }}</span>
                        <a href="{{ $dashboardUrl }}"><i class="fi fi-rr-dashboard"></i> View Dashboard</a>
                        <a href="{{ url('/explore') }}"><i class="fi fi-rr-search"></i> Continue Browsing</a>
                    </div>
                </div>
            @else
                <a class="nav-login" href="{{ url('/user/login') }}">Login</a>
                <a class="nav-join" href="{{ url('/register') }}">Sign Up</a>
            @endif
        </div>
    </nav>
</header>
