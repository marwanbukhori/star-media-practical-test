(function () {
  'use strict';

  var overlay = document.querySelector('[data-smg-consent-overlay]');
  if (!overlay) {
    return;
  }

  var dialog = overlay.querySelector('[data-smg-consent-dialog]');
  var acceptBtn = overlay.querySelector('[data-smg-consent-accept]');
  // The dialog's form, plus the non-modal bar's form on privacy.php / terms.php.
  var forms = document.querySelectorAll('[data-smg-consent-form]');
  var banners = document.querySelectorAll('[data-smg-consent-banner]');
  var savedScrollY = 0;

  function submitButtons(form) {
    return form.querySelectorAll('button[type="submit"]');
  }

  // Captured before the submit handler ever mutates a button's label, so a dialog
  // reopened after a prior submit (e.g. via "Cookie settings") can be reset to it.
  for (var f = 0; f < forms.length; f++) {
    var initialButtons = submitButtons(forms[f]);
    for (var b = 0; b < initialButtons.length; b++) {
      initialButtons[b].dataset.originalLabel = initialButtons[b].textContent;
    }
  }

  function resetForm(form) {
    var buttons = submitButtons(form);
    for (var i = 0; i < buttons.length; i++) {
      buttons[i].disabled = false;
      buttons[i].textContent = buttons[i].dataset.originalLabel;
    }
    form.removeAttribute('aria-busy');
  }

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
    for (var i = 0; i < forms.length; i++) {
      resetForm(forms[i]);
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

  function onChoiceSaved() {
    if (!overlay.hidden) {
      closeDialog();
    }
    for (var i = 0; i < banners.length; i++) {
      banners[i].hidden = true;
    }
  }

  // Native POST fallback. form.submit() doesn't include the clicked button, so its
  // name/value (action=accept|decline) is carried over in a hidden input first.
  function submitNatively(form, submitter) {
    if (submitter && submitter.name) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = submitter.name;
      input.value = submitter.value;
      form.appendChild(input);
    }
    form.submit();
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

  function handleSubmit(e) {
    e.preventDefault();

    var form = e.currentTarget;
    var submitter = e.submitter;
    var formData = new FormData(form);
    if (submitter && submitter.name) {
      formData.set(submitter.name, submitter.value);
    }

    var buttons = submitButtons(form);
    for (var i = 0; i < buttons.length; i++) {
      buttons[i].disabled = true;
    }
    if (submitter) {
      var spinner = document.createElement('span');
      spinner.className = 'smg-btn__spinner';
      spinner.setAttribute('aria-hidden', 'true');
      submitter.textContent = '';
      submitter.appendChild(spinner);
      submitter.appendChild(document.createTextNode(submitter.dataset.originalLabel + '…'));
    }
    form.setAttribute('aria-busy', 'true');

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
          onChoiceSaved();
        } else {
          submitNatively(form, submitter);
        }
      })
      .catch(function () {
        submitNatively(form, submitter);
      });
  }

  for (var s = 0; s < forms.length; s++) {
    forms[s].addEventListener('submit', handleSubmit);
  }

  var reopenLinks = document.querySelectorAll('[data-smg-reopen-consent]');
  for (var r = 0; r < reopenLinks.length; r++) {
    reopenLinks[r].addEventListener('click', function (e) {
      e.preventDefault();
      openDialog(true);
    });
  }
})();
