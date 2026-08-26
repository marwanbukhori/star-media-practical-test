(function () {
  'use strict';

  // ---------------------------------------------------------------- password show/hide

  var toggle = document.querySelector('[data-smg-password-toggle]');
  if (toggle) {
    var passwordInput = document.getElementById('password');
    var eyeIcon = toggle.querySelector('[data-smg-eye-icon]');
    var eyeOffIcon = toggle.querySelector('[data-smg-eye-off-icon]');

    function setHidden(el, isHidden) {
      // .hidden assignment doesn't reliably reflect on SVG elements in every
      // browser — use the attribute directly instead.
      if (isHidden) {
        el.setAttribute('hidden', '');
      } else {
        el.removeAttribute('hidden');
      }
    }

    toggle.addEventListener('click', function () {
      var showing = passwordInput.type === 'text';
      passwordInput.type = showing ? 'password' : 'text';
      toggle.setAttribute('aria-pressed', showing ? 'false' : 'true');
      toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
      setHidden(eyeIcon, !showing);
      setHidden(eyeOffIcon, showing);
    });
  }

  // ---------------------------------------------------------------- submit loading state
  // Still a real form POST underneath — this only adds visual feedback while the
  // browser processes the navigation, it never prevents the submit.

  var loginForm = document.querySelector('[data-smg-login-form]');
  if (loginForm) {
    loginForm.addEventListener('submit', function () {
      var submitBtn = loginForm.querySelector('[data-smg-login-submit]');
      if (submitBtn) {
        submitBtn.classList.add('is-loading');
        submitBtn.disabled = true;
      }
    });
  }

  // ---------------------------------------------------------------- rate-limit countdown

  var banner = document.querySelector('[data-smg-retry-after]');
  if (banner) {
    var remaining = parseInt(banner.getAttribute('data-smg-retry-after'), 10) || 0;
    var baseText = 'Too many attempts. Try again in ';

    var timer = setInterval(function () {
      remaining -= 1;
      if (remaining <= 0) {
        clearInterval(timer);
        banner.textContent = 'You can try again now.';
        return;
      }
      banner.textContent = baseText + remaining + 's.';
    }, 1000);
  }
})();
