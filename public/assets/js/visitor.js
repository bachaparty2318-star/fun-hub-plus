(() => {
  const navToggle = document.querySelector('.nav-toggle');
  const navMenu = document.querySelector('#visitorMenu');
  if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => {
      const open = navMenu.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', String(open));
    });
  }

  const aosTargets = document.querySelectorAll('.home-hero-panel, .hero-card, .section-head, .filter-bar, .content-card, .mini-card, .article-panel, .side-panel, .calendar-card, .auth-card, .site-footer');
  aosTargets.forEach((element, index) => {
    if (!element.dataset.aos) element.dataset.aos = index % 4 === 0 ? 'fade-up' : 'fade-up';
    element.dataset.aosDuration = element.dataset.aosDuration || '720';
    element.dataset.aosEasing = element.dataset.aosEasing || 'ease-out-cubic';
    element.dataset.aosDelay = element.dataset.aosDelay || String(Math.min(index % 7, 5) * 45);
    element.dataset.aosOnce = 'true';
  });

  if (window.AOS) {
    window.AOS.init({
      duration: 720,
      easing: 'ease-out-cubic',
      offset: 70,
      once: true,
      mirror: false
    });
  }

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const revealTargets = document.querySelectorAll('.home-hero-panel, .hero-card, .filter-bar, .content-card, .article-panel, .side-panel, .mini-card, .calendar-card, .card, .section-head, .auth-card');

  revealTargets.forEach((element, index) => {
    element.dataset.reveal = '';
    element.style.transitionDelay = reduceMotion ? '0ms' : `${Math.min(index % 9, 6) * 55}ms`;
  });

  if (reduceMotion || !('IntersectionObserver' in window)) {
    revealTargets.forEach(element => element.classList.add('is-visible'));
    return;
  }

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      observer.unobserve(entry.target);
    });
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

  revealTargets.forEach(element => observer.observe(element));
})();
