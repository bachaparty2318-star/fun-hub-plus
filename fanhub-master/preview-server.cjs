const http = require('http');
const fs = require('fs');
const path = require('path');

const root = __dirname;
const port = Number(process.env.PORT || 8080);
const mime = { '.css': 'text/css; charset=utf-8', '.js': 'application/javascript; charset=utf-8', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.jfif': 'image/jpeg', '.png': 'image/png', '.webp': 'image/webp', '.gif': 'image/gif', '.mp4': 'video/mp4' };
const asset = file => `/assets/images/${file}`;
const cards = [
  ['Demon Slayer Season 4 Trailer', 'Anime', asset('Content/Demon Slayer Season 4 Trailer.jfif')],
  ['BLACKPINK Comeback Teaser', 'K-Pop', asset('Content/BLACKPINK Comeback Teaser.jfif')],
  ['Dune Part 3 First Look', 'Movies', asset('Content/Dune Part 3 First Look.jfif')],
  ['One Piece Chapter 1120 Review', 'Manga', asset('Content/One Piece Chapter 1120 Review.jfif')],
  ['Best Cosplays from Comic-Con 2026', 'Cosplay', asset('Content/Best Cosplays from Comic-Con 2026.jfif')],
  ['Spider-Man New Villain Arc', 'Comics', asset('Content/Spider-Man New Villain Arc.jfif')],
];
const characters = [
  ['Tanjiro Kamado', 'Anime', asset('Character profiles/Tanjiro Kamado.jfif')],
  ['Jisoo Park', 'K-Pop', asset('Character profiles/Jisoo Park.jfif')],
  ['Ellie Williams', 'Gaming', asset('Character profiles/Ellie Williams.jfif')],
  ['Miles Morales', 'Comics', asset('Character profiles/Miles Morales.jfif')],
];
const merch = [
  ['Spider-Man Hoodie', 'Standard', asset('Merchandise/Spider-Man Hoodie.jfif')],
  ['Nezuko Figure', 'Collectible', asset('Merchandise/Nezuko Figure.jfif')],
  ['BLACKPINK Lightstick', 'Limited Edition', asset('Merchandise/BLACKPINK Lightstick.jfif')],
];

function readView(file) {
  return fs.readFileSync(path.join(root, 'resources/views', file), 'utf8')
    .replaceAll("{{ str_replace('_', '-', app()->getLocale()) }}", 'en')
    .replaceAll("{{ date('Y') }}", String(new Date().getFullYear()))
    .replaceAll("{{ asset('assets/images/hero section/hero-anime.jfif') }}", asset('hero section/hero-anime.jfif'));
}
function styles() { return readView('visitor/_styles.blade.php'); }
function footer() {
  return `<footer class="site-footer"><div class="footer-inner"><div><a class="brand" href="/"><span class="brand-mark">F+</span><span>Fan Hub Plus</span></a><p>About Fan Hub Plus: a fandom discovery hub for categories, articles, characters, merchandise, events and searchable fan content.</p><p class="copyright">© ${new Date().getFullYear()} Fan Hub Plus. Visitor access is browse-first; member actions require login.</p></div><div><p class="footer-title">Quick links</p><nav class="footer-links"><a href="/sitemap">Sitemap</a><a href="/category/anime">Anime</a><a href="/category/gaming">Gaming</a><a href="/category/movies">Movies</a><a href="/category/k-pop">K-Pop</a><a href="/category/cosplay">Cosplay</a><a href="/feedback">Feedback</a></nav><nav class="social-links"><a href="#"><i class="fi fi-rr-camera"></i></a><a href="#"><i class="fi fi-rr-comment-alt"></i></a><a href="#"><i class="fi fi-rr-play-alt"></i></a></nav></div></div></footer>`;
}
function base(title, body) {
  return `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${title} · Fan Hub Plus</title>${styles()}</head><body><nav class="page-nav"><a class="brand" href="/"><span class="brand-mark">F+</span><span>Fan Hub Plus</span></a><div class="nav-links"><a href="/">Home</a><a href="/explore">Explore</a><a href="/articles">Articles</a><a href="/events">Events</a><a href="/characters">Characters</a><a href="/login"><i class="fi fi-rr-sign-in-alt"></i> Login</a><a href="/register" class="is-active"><i class="fi fi-rr-user-add"></i> Register</a></div></nav><main class="shell">${body}</main>${footer()}</body></html>`;
}
function grid(items, prefix = '/content/content/') {
  return `<section class="grid">${items.map((item, index) => `<a class="content-card" href="${prefix}${index + 1}"><article><div class="card-media"><img src="${item[2]}" alt="${item[0]}"></div><div class="card-body"><span class="pill">${item[1]}</span><h2>${item[0]}</h2><p class="muted">Route-aware preview. Real Laravel route uses DB data when PHP 8.2 is available.</p></div></article></a>`).join('')}</section>`;
}
function auth(register) {
  return `<section class="auth-wrap"><div class="auth-card"><div class="tags"><a class="pill ${!register ? 'btn alt' : ''}" href="/login"><i class="fi fi-rr-sign-in-alt"></i> Login</a><a class="pill ${register ? 'btn alt' : ''}" href="/register"><i class="fi fi-rr-user-add"></i> Register</a></div><p class="kicker">Member Access</p><h1>${register ? 'Create account' : 'Welcome back'}</h1><form>${register ? '<div class="field"><label>Name</label><input></div>' : ''}<div class="field"><label>Email</label><input type="email"></div><div class="field"><label>Password</label><input type="password"></div><button class="btn">${register ? 'Register' : 'Login'}</button></form><p class="auth-note"><a href="/forgot-password">Forgot password?</a></p></div></section>`;
}
function page(url) {
  const slug = url.pathname.split('/').filter(Boolean).pop() || 'home';
  if (url.pathname === '/') return readView('welcome.blade.php');
  if (url.pathname === '/events') return base('Events', `<nav class="crumbs"><a href="/">Home</a><span>›</span><span>Events</span></nav><section class="hero-card"><p class="kicker">Calendar</p><h1>Upcoming fandom events</h1><p class="lead">Calendar + upcoming list + map/ticket preview.</p></section><section class="calendar-layout"><div class="calendar-card"><h2>October 2026</h2><div class="calendar-grid">${['SUN','MON','TUE','WED','THU','FRI','SAT'].map(day=>`<div class="cal-head">${day}</div>`).join('')}${Array.from({length:35},(_,i)=>`<div class="cal-day">${i+1}${[10,11,12,13,14].includes(i+1)?'<span class="cal-event">Fan event</span>':''}</div>`).join('')}</div></div><aside class="side-panel"><p class="kicker">Upcoming List</p>${cards.slice(0,5).map(c=>`<article class="mini-card"><h3>${c[0]}</h3><p class="muted">${c[1]} · Lahore · Ticket link if available</p><div class="map-preview">Map preview</div></article>`).join('')}</aside></section>`);
  if (url.pathname === '/explore') return base('Explore', `<nav class="crumbs"><a href="/">Home</a><span>›</span><span>Explore</span></nav><section class="hero-card"><p class="kicker">Universal Search</p><h1>Search every fandom table</h1><p class="lead">Advanced filters + mixed results grid.</p></section><form class="filter-bar"><div class="field"><label>Search</label><input placeholder="Search fandoms"></div><div class="field"><label>Sort</label><select><option>Latest</option><option>Popular</option></select></div><button class="btn">Search</button></form>${grid(cards)}`);
  if (url.pathname === '/articles') return base('Articles', `<nav class="crumbs"><a href="/">Home</a><span>›</span><span>Articles</span></nav><section class="hero-card"><p class="kicker">Articles Hub</p><h1>Featured stories first</h1></section>${grid(cards, '/article/')}`);
  if (url.pathname === '/characters') return base('Characters', `<nav class="crumbs"><a href="/">Home</a><span>›</span><span>Characters</span></nav><section class="hero-card"><p class="kicker">Character Profiles</p><h1>Characters fans keep talking about</h1></section>${grid(characters, '/character/')}`);
  if (url.pathname === '/merchandise') return base('Merchandise', `<nav class="crumbs"><a href="/">Home</a><span>›</span><span>Merchandise</span></nav><section class="hero-card"><p class="kicker">Display-only Showcase</p><h1>Fan gear and collectibles</h1><p class="lead">No buy button.</p></section>${grid(merch, '/merchandise/')}`);
  if (url.pathname === '/login') return base('Login', auth(false));
  if (url.pathname === '/register') return base('Register', auth(true));
  if (url.pathname === '/feedback' || url.pathname === '/submissions') return base('Login required', auth(false));
  if (url.pathname.startsWith('/category/')) return base(`Category ${slug}`, `<nav class="crumbs"><a href="/">Home</a><span>›</span><span>${slug}</span></nav><section class="hero-card"><p class="kicker">Category Page</p><h1>${slug.replaceAll('-', ' ')}</h1><p class="lead">Category banner, filters and paginated content grid.</p></section>${grid(cards)}`);
  if (url.pathname === '/sitemap') return base('Sitemap', `<nav class="crumbs"><a href="/">Home</a><span>›</span><span>Sitemap</span></nav><section class="hero-card"><p class="kicker">Sitemap</p><h1>Site structure</h1><p class="lead">Home, categories, content, articles, characters, merchandise, events, explore, auth and member-only actions.</p></section>${grid([['Explore','Search','/assets/images/hero section/hero-anime.jfif'],['Events','Calendar','/assets/images/hero section/hero-cosplay.jfif'],['Articles','Stories','/assets/images/hero section/hero-kpop.jfif']], '/')}`);
  return base('Fan Hub Plus', `<section class="hero-card"><p class="kicker">Route Preview</p><h1>${url.pathname}</h1><p class="lead">This route exists in Laravel or redirects to login. Static preview fallback is active.</p></section>`);
}

http.createServer((req, res) => {
  const url = new URL(req.url, `http://127.0.0.1:${port}`);
  const filePath = path.normalize(path.join(root, 'public', decodeURIComponent(url.pathname)));
  if (filePath.startsWith(path.join(root, 'public')) && fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
    res.writeHead(200, { 'Content-Type': mime[path.extname(filePath).toLowerCase()] || 'application/octet-stream' });
    fs.createReadStream(filePath).pipe(res);
    return;
  }
  res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
  res.end(page(url));
}).listen(port, '127.0.0.1', () => console.log(`Fan Hub Plus preview running at http://127.0.0.1:${port}`));
