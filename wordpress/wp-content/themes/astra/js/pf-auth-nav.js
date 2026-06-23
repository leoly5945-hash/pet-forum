(function () {
  'use strict';

  document.addEventListener('click', function (e) {
    var nav = document.getElementById('pfAuthNav');
    if (!nav) return;

    var btn = nav.querySelector('.pf-auth-btn');
    var menu = document.getElementById('pfAuthMenu');
    if (!btn || !menu) return;

    if (btn.contains(e.target)) {
      var open = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', open ? 'false' : 'true');
      menu.hidden = open;
      return;
    }

    if (!nav.contains(e.target)) {
      btn.setAttribute('aria-expanded', 'false');
      menu.hidden = true;
    }
  });
})();
