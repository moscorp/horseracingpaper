(function () {
  'use strict';

  function initNav(root) {
    root.querySelectorAll('[data-nav-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var expanded = btn.getAttribute('aria-expanded') === 'true';
        var next = !expanded;
        btn.setAttribute('aria-expanded', next ? 'true' : 'false');
        var caret = btn.querySelector('[data-nav-caret]');
        if (caret) {
          caret.textContent = next ? '−' : '+';
        }
        var panel = btn.nextElementSibling;
        if (panel && panel.classList.contains('hrp-nav-panel')) {
          if (next) {
            panel.removeAttribute('hidden');
          } else {
            panel.setAttribute('hidden', '');
          }
        }
      });
    });

    root.querySelectorAll('[data-nav-more]').forEach(function (btn) {
      btn.addEventListener('click', function () {
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
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-hrp-nav]').forEach(initNav);
  });
})();
