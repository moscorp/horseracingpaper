(function () {
  'use strict';

  function revealMore(btn) {
    var group = btn.closest('[data-nav-group]');
    if (!group) {
      return;
    }
    var extras = group.querySelectorAll('.hrp-nav-extra[hidden]');
    if (extras.length) {
      extras.forEach(function (el) {
        el.removeAttribute('hidden');
      });
      var archive = btn.getAttribute('data-archive');
      if (archive) {
        var link = document.createElement('a');
        link.className = btn.className;
        link.href = archive;
        link.textContent = '查看全部分類';
        btn.replaceWith(link);
      } else {
        btn.remove();
      }
      return;
    }
    var archiveUrl = btn.getAttribute('data-archive');
    if (archiveUrl) {
      window.location.href = archiveUrl;
    }
  }

  function initNav(root) {
    if (!root || root.getAttribute('data-hrp-nav-ready') === '1') {
      return;
    }
    root.setAttribute('data-hrp-nav-ready', '1');

    // Event delegation — survives LiteSpeed/HTML mutations better than per-button binds.
    root.addEventListener('click', function (e) {
      var more = e.target.closest('[data-nav-more]');
      if (more && root.contains(more)) {
        e.preventDefault();
        revealMore(more);
      }
    });

    // Keep title links from also toggling <details> when clicked.
    root.querySelectorAll('summary .hrp-nav-title').forEach(function (link) {
      link.addEventListener('click', function (e) {
        e.stopPropagation();
      });
    });
  }

  function boot() {
    document.querySelectorAll('[data-hrp-nav]').forEach(initNav);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
