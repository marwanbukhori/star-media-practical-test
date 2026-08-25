(function () {
  'use strict';

  var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---------------------------------------------------------------- carousels
  // Base markup is just a scroll-snap track — fully usable via touch/trackpad
  // with zero JS. Arrow buttons and dot indicators are injected here, on top.

  function initCarousel(root) {
    var track = root.querySelector('[data-smg-carousel-track]');
    if (!track) return;
    var panels = Array.prototype.slice.call(track.children);
    if (panels.length < 2) return;

    var controls = document.createElement('div');
    controls.className = 'smg-carousel__controls';

    var prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'smg-carousel__arrow';
    prevBtn.setAttribute('aria-label', 'Previous');
    prevBtn.textContent = '‹';

    var dots = document.createElement('div');
    dots.className = 'smg-carousel__dots';

    var nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'smg-carousel__arrow';
    nextBtn.setAttribute('aria-label', 'Next');
    nextBtn.textContent = '›';

    var dotEls = panels.map(function (_, i) {
      var dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'smg-carousel__dot';
      dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
      dot.addEventListener('click', function () {
        scrollToPanel(i);
      });
      dots.appendChild(dot);
      return dot;
    });

    function activeIndex() {
      var trackLeft = track.getBoundingClientRect().left;
      var closest = 0;
      var closestDist = Infinity;
      panels.forEach(function (p, i) {
        var dist = Math.abs(p.getBoundingClientRect().left - trackLeft);
        if (dist < closestDist) {
          closestDist = dist;
          closest = i;
        }
      });
      return closest;
    }

    function updateDots() {
      var current = activeIndex();
      dotEls.forEach(function (d, i) {
        d.classList.toggle('is-active', i === current);
      });
    }

    function scrollToPanel(i) {
      var clamped = Math.max(0, Math.min(panels.length - 1, i));
      // Scroll the track itself (horizontal only) — never scrollIntoView, which scrolls
      // the whole page vertically too when the carousel isn't already in the viewport.
      track.scrollTo({ left: panels[clamped].offsetLeft, behavior: reducedMotion ? 'auto' : 'smooth' });
    }

    prevBtn.addEventListener('click', function () {
      scrollToPanel(activeIndex() - 1);
    });
    nextBtn.addEventListener('click', function () {
      scrollToPanel(activeIndex() + 1);
    });

    var scrollTimer;
    track.addEventListener('scroll', function () {
      clearTimeout(scrollTimer);
      scrollTimer = setTimeout(updateDots, 100);
    });

    controls.appendChild(prevBtn);
    controls.appendChild(dots);
    controls.appendChild(nextBtn);
    root.appendChild(controls);
    updateDots();
  }

  document.querySelectorAll('[data-smg-carousel]').forEach(initCarousel);

  // ---------------------------------------------------------------- scroll-reveal

  var revealEls = document.querySelectorAll('[data-reveal]');
  if (revealEls.length && 'IntersectionObserver' in window) {
    var revealObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-revealed');
            revealObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15 }
    );
    revealEls.forEach(function (el) {
      revealObserver.observe(el);
    });
  } else {
    revealEls.forEach(function (el) {
      el.classList.add('is-revealed');
    });
  }

  // ---------------------------------------------------------------- count-up
  // Final value already lives in the server-rendered text — this only animates
  // the visual count, so JS-off / no-IntersectionObserver visitors still see
  // the correct number immediately.

  var countEls = document.querySelectorAll('[data-count-up]');
  if (countEls.length && 'IntersectionObserver' in window && !reducedMotion) {
    var countObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          countObserver.unobserve(entry.target);
          animateCount(entry.target);
        });
      },
      { threshold: 0.6 }
    );
    countEls.forEach(function (el) {
      countObserver.observe(el);
    });
  }

  function animateCount(el) {
    var finalText = el.textContent.trim();
    var match = finalText.match(/^([\d.]+)(.*)$/);
    if (!match) return;
    var endNum = parseFloat(match[1]);
    var decimals = match[1].indexOf('.') !== -1 ? 1 : 0;
    var suffix = match[2];
    var duration = 900;
    var start = null;

    function tick(timestamp) {
      if (start === null) start = timestamp;
      var progress = Math.min(1, (timestamp - start) / duration);
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = (endNum * eased).toFixed(decimals) + suffix;
      if (progress < 1) {
        requestAnimationFrame(tick);
      } else {
        el.textContent = finalText;
      }
    }

    requestAnimationFrame(tick);
  }

  // ---------------------------------------------------------------- recognition auto-advance

  var recognitionTrack = document.querySelector('[data-smg-auto-carousel] [data-smg-carousel-track]');
  if (recognitionTrack && !reducedMotion) {
    var autoTimer;
    var panels = Array.prototype.slice.call(recognitionTrack.children);

    function nextPanel() {
      var trackLeft = recognitionTrack.getBoundingClientRect().left;
      var current = 0;
      var closestDist = Infinity;
      panels.forEach(function (p, i) {
        var dist = Math.abs(p.getBoundingClientRect().left - trackLeft);
        if (dist < closestDist) {
          closestDist = dist;
          current = i;
        }
      });
      var next = (current + 1) % panels.length;
      // Scroll the track itself (horizontal only) — never scrollIntoView, which would
      // also scroll the whole page down to this section if it isn't visible yet.
      recognitionTrack.scrollTo({ left: panels[next].offsetLeft, behavior: 'smooth' });
    }

    function start() {
      if (!autoTimer) autoTimer = setInterval(nextPanel, 5000);
    }
    function stop() {
      clearInterval(autoTimer);
      autoTimer = null;
    }

    recognitionTrack.addEventListener('mouseenter', stop);
    recognitionTrack.addEventListener('mouseleave', start);
    recognitionTrack.addEventListener('focusin', stop);
    recognitionTrack.addEventListener('focusout', start);

    // Only auto-advance while the carousel is actually on screen.
    if ('IntersectionObserver' in window) {
      var visibilityObserver = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              start();
            } else {
              stop();
            }
          });
        },
        { threshold: 0.4 }
      );
      visibilityObserver.observe(recognitionTrack);
    } else {
      start();
    }
  }
})();
