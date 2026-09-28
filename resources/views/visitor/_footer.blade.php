<footer class="site-footer">
    <div class="footer-inner">
        <div>
            <a class="brand" href="{{ url('/') }}">
                <span>FanHubPlus</span>
            </a>
            <p>Fan Hub Plus brings anime, gaming, movies, TV shows, K-Pop, comics, manga and cosplay into one polished discovery space.</p>
            <p class="copyright">© {{ date('Y') }} Fan Hub Plus. Visitors can browse; bookmarks, ratings, submissions and feedback require member login.</p>
        </div>
        <div class="footer-groups">
            <div>
                <p class="footer-title">Explore</p>
                <nav class="footer-links" aria-label="Explore links">
                    <a href="{{ url('/explore') }}">Universal Search</a>
                    <a href="{{ url('/characters') }}">Characters</a>
                    <a href="{{ url('/articles') }}">Articles</a>
                    <a href="{{ url('/events') }}">Events</a>
                    <a href="{{ url('/sitemap') }}">Sitemap</a>
                </nav>
            </div>
            <div>
                <p class="footer-title">Member</p>
                <nav class="footer-links" aria-label="Member links">
                    <a href="{{ url('/register') }}">Join Fan Hub</a>
                    <a href="{{ url('/user/login') }}">Login</a>
                    <a href="{{ url('/forgot-password') }}">Forgot Password</a>
                    <a href="{{ url('/feedback') }}">Feedback</a>
                </nav>
            </div>
        </div>
    </div>
</footer>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script src="{{ asset('assets/js/visitor.js') }}"></script>
