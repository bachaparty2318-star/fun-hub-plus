(() => {
  function applyMemberAos() {
    const items = document.querySelectorAll('.member-page-head, .member-panel, .catalog-card, .member-record-card, .account-section, .calendar-shell, .member-event-card, .activity-card, .bookmark-mini, .modal, .quickbar button');
    items.forEach((element, index) => {
      if (!element.dataset.aos) element.dataset.aos = 'fade-up';
      element.dataset.aosDuration = element.dataset.aosDuration || '650';
      element.dataset.aosDelay = element.dataset.aosDelay || String(Math.min(index % 6, 5) * 35);
      element.dataset.aosOnce = 'true';
    });
    if (window.AOS) {
      window.AOS.init({ duration: 650, easing: 'ease-out-cubic', offset: 55, once: true, mirror: false });
      window.AOS.refreshHard();
    }
  }
  window.MemberAos = { refresh: applyMemberAos };
  applyMemberAos();
  setInterval(applyMemberAos, 1200);

  function normalise(value) { return String(value || '').trim(); }
  function initials(name) { return normalise(name).split(/\s+/).filter(Boolean).slice(0, 2).map(part => part.charAt(0).toUpperCase()).join('') || 'M'; }
  function render(element, url, name) {
    if (!element) return;
    const source = normalise(url).replace('/storage/', '/media/');
    element.classList.toggle('avatar-empty', !source);
    element.innerHTML = source ? `<img src="${source.replace(/&/g, '&amp;').replace(/"/g, '&quot;')}" alt="">` : initials(name);
  }
  window.MemberAvatar = { render, initials, normalise };
})();
