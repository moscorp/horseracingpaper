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

    // Capture phase: <a> inside <summary> both navigates and toggles in Chromium.
    root.addEventListener(
      'click',
      function (e) {
        var archive = e.target.closest('[data-nav-archive]');
        if (archive && root.contains(archive)) {
          e.preventDefault();
          e.stopPropagation();
          var href = archive.getAttribute('href');
          if (href) {
            window.location.href = href;
          }
          return;
        }

        var more = e.target.closest('[data-nav-more]');
        if (more && root.contains(more)) {
          e.preventDefault();
          e.stopPropagation();
          revealMore(more);
        }
      },
      true
    );
  }

  /**
   * Same-origin /00 tool iframes ship max-width:1400px — inject full-bleed CSS.
   */
  function widenToolFrame(iframe) {
    if (!iframe || iframe.getAttribute('data-hrp-widen-ready') === '1') {
      return;
    }
    function apply() {
      try {
        var doc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
        if (!doc || !doc.head) {
          return;
        }
        if (doc.getElementById('hrp-widen-embed')) {
          iframe.setAttribute('data-hrp-widen-ready', '1');
          return;
        }
        var style = doc.createElement('style');
        style.id = 'hrp-widen-embed';
        style.textContent =
          'html,body{width:100%!important;max-width:none!important;}' +
          '.container,.app-content,.app{max-width:none!important;width:100%!important;}' +
          'table{width:100%!important;}';
        doc.head.appendChild(style);
        iframe.setAttribute('data-hrp-widen-ready', '1');
      } catch (err) {
        /* cross-origin or not ready */
      }
    }
    iframe.addEventListener('load', apply);
    apply();
  }

  function boot() {
    document.querySelectorAll('[data-hrp-nav]').forEach(initNav);
    document.querySelectorAll('iframe[data-hrp-widen="1"]').forEach(widenToolFrame);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
