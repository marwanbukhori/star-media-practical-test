(function () {
  'use strict';

  var overlay = document.querySelector('[data-smg-consent-overlay]');
  if (!overlay) {
    return;
  }

  var dialog = overlay.querySelector('[data-smg-consent-dialog]');
  var form = overlay.querySelector('[data-smg-consent-form]');
  var acceptBtn = overlay.querySelector('[data-smg-consent-accept]');
  var savedScrollY = 0;

  function isDismissible() {
    return overlay.dataset.dismissible === '1';
  }

  function setBackgroundInert(makeInert) {
    var children = document.body.children;
    for (var i = 0; i < children.length; i++) {
      var el = children[i];
      if (el === overlay) {
        continue;
      }
      if (makeInert) {
        el.setAttribute('inert', '');
      } else {
        el.removeAttribute('inert');
      }
    }
  }

  function lockScroll() {
    savedScrollY = window.scrollY;
    document.documentElement.classList.add('smg-locked');
    document.body.style.position = 'fixed';
    document.body.style.top = '-' + savedScrollY + 'px';
    document.body.style.left = '0';
    document.body.style.right = '0';
  }

  function unlockScroll() {
    document.documentElement.classList.remove('smg-locked');
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.left = '';
    document.body.style.right = '';
    window.scrollTo(0, savedScrollY);
  }

  function openDialog(makeDismissible) {
    if (makeDismissible) {
      overlay.dataset.dismissible = '1';
    }
    overlay.hidden = false;
    lockScroll();
    setBackgroundInert(true);
    if (acceptBtn) {
      acceptBtn.focus();
    }
  }

  function closeDialog() {
    overlay.hidden = true;
    unlockScroll();
    setBackgroundInert(false);
  }

  // Server rendered the dialog already open (blocking gate, or ?consent=manage).
  if (!overlay.hidden) {
    savedScrollY = window.scrollY;
    document.body.style.position = 'fixed';
    document.body.style.top = '-' + savedScrollY + 'px';
    document.body.style.left = '0';
    document.body.style.right = '0';
    setBackgroundInert(true);
    if (acceptBtn) {
      acceptBtn.focus();
    }
  }

  if (dialog) {
    dialog.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        if (isDismissible()) {
          closeDialog();
        }
        return;
      }

      if (e.key !== 'Tab') {
        return;
      }

      var focusable = dialog.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
      );
      if (!focusable.length) {
        return;
      }

      var first = focusable[0];
      var last = focusable[focusable.length - 1];

      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    });
  }

  overlay.addEventListener('click', function (e) {
    if (e.target === overlay && isDismissible()) {
      closeDialog();
    }
  });

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var formData = new FormData(form);
      var submitter = e.submitter;
      if (submitter && submitter.name) {
        formData.set(submitter.name, submitter.value);
      }

      fetch(form.getAttribute('action'), {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'fetch' },
        credentials: 'same-origin',
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          if (data && data.ok) {
            closeDialog();
          } else {
            form.submit();
          }
        })
        .catch(function () {
          form.submit();
        });
    });
  }

  var reopenLinks = document.querySelectorAll('[data-smg-reopen-consent]');
  for (var i = 0; i < reopenLinks.length; i++) {
    reopenLinks[i].addEventListener('click', function (e) {
      e.preventDefault();
      openDialog(true);
    });
  }
})();
