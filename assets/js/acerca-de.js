(function () {
  'use strict';

  var panel = document.querySelector('[data-about-panel]');
  var home = document.querySelector('[data-about-home]');
  var openButton = document.querySelector('[data-about-open]');

  if (!panel || !home || !openButton) {
    return;
  }

  var slides = Array.from(panel.querySelectorAll('[data-about-slide]'));
  var dots = Array.from(panel.querySelectorAll('.about-dot'));
  var closeButton = panel.querySelector('[data-about-close]');
  var pauseButton = panel.querySelector('[data-about-pause]');
  var status = panel.querySelector('[data-about-status]');
  var interval = Number(panel.dataset.aboutInterval) || 20000;
  var current = 0;
  var timer = null;
  var paused = false;

  function stopTimer() {
    window.clearTimeout(timer);
    timer = null;
  }

  function scheduleNext() {
    stopTimer();

    if (!panel.hidden && !paused && !document.hidden) {
      timer = window.setTimeout(function () {
        showSlide(current + 1, false);
      }, interval);
    }
  }

  function showSlide(index, announce) {
    var next = (index + slides.length) % slides.length;
    var focusedSlide = slides[current].contains(document.activeElement);

    slides.forEach(function (slide, slideIndex) {
      var active = slideIndex === next;
      slide.classList.toggle('is-active', active);
      slide.setAttribute('aria-hidden', String(!active));
      slide.toggleAttribute('inert', !active);
    });

    dots.forEach(function (dot, dotIndex) {
      var active = dotIndex === next;
      dot.classList.toggle('is-active', active);
      if (active) {
        dot.setAttribute('aria-current', 'true');
      } else {
        dot.removeAttribute('aria-current');
      }
    });

    if (next !== current && focusedSlide) {
      dots[next].focus({ preventScroll: true });
    }

    current = next;
    if (announce) {
      status.textContent = next === 0 ? 'Contacto de los desarrolladores. Mensaje 1 de 2.' : 'Acerca del proyecto. Mensaje 2 de 2.';
    }
    scheduleNext();
  }

  function closePanel() {
    stopTimer();
    panel.hidden = true;
    home.hidden = false;
    openButton.hidden = false;
    openButton.setAttribute('aria-expanded', 'false');
    openButton.focus({ preventScroll: true });
  }

  openButton.addEventListener('click', function () {
    home.hidden = true;
    openButton.hidden = true;
    panel.hidden = false;
    openButton.setAttribute('aria-expanded', 'true');
    paused = false;
    pauseButton.setAttribute('aria-pressed', 'false');
    pauseButton.textContent = 'Pausar';
    status.textContent = '';
    showSlide(0, false);
    closeButton.focus({ preventScroll: true });
  });

  closeButton.addEventListener('click', closePanel);
  panel.querySelector('[data-about-prev]').addEventListener('click', function () {
    showSlide(current - 1, true);
  });
  panel.querySelector('[data-about-next]').addEventListener('click', function () {
    showSlide(current + 1, true);
  });

  panel.querySelectorAll('[data-about-go]').forEach(function (button) {
    button.addEventListener('click', function () {
      showSlide(Number(button.dataset.aboutGo), true);
    });
  });

  function copyWithSelection(email) {
    var field = document.createElement('textarea');
    var previousFocus = document.activeElement;
    field.value = email;
    field.className = 'about-copy-buffer';
    field.setAttribute('readonly', '');
    field.setAttribute('aria-hidden', 'true');
    document.body.appendChild(field);

    try {
      field.focus({ preventScroll: true });
      field.select();
      field.setSelectionRange(0, email.length);
      return document.execCommand('copy');
    } catch (error) {
      return false;
    } finally {
      field.remove();
      if (previousFocus) {
        previousFocus.focus({ preventScroll: true });
      }
    }
  }

  function copyEmail(email) {
    if (window.isSecureContext && navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(email).then(function () {
        return true;
      }, function () {
        return copyWithSelection(email);
      });
    }

    return Promise.resolve(copyWithSelection(email));
  }

  panel.querySelectorAll('[data-about-email]').forEach(function (emailButton) {
    var copyButton = emailButton.parentElement.querySelector('[data-about-copy]');
    var copyLabel = copyButton.querySelector('[data-about-copy-label]');
    var emailValue = emailButton.querySelector('[data-about-email-value]');
    var feedbackTimer = null;

    emailButton.addEventListener('click', function () {
      copyButton.hidden = !copyButton.hidden;
      emailButton.setAttribute('aria-expanded', String(!copyButton.hidden));
      scheduleNext();
    });

    copyButton.addEventListener('click', function () {
      var email = emailValue.textContent.trim();
      window.clearTimeout(feedbackTimer);
      copyButton.classList.remove('is-copied');
      copyButton.disabled = true;
      copyLabel.textContent = 'Copiando…';
      scheduleNext();

      copyEmail(email).then(function (copied) {
        copyButton.disabled = false;
        copyButton.classList.toggle('is-copied', copied);
        copyLabel.textContent = copied ? 'Copiado' : 'Reintentar';
        status.textContent = copied ? 'Correo copiado: ' + email : 'No se pudo copiar el correo. Selecciónalo para copiarlo manualmente.';
        feedbackTimer = window.setTimeout(function () {
          copyButton.classList.remove('is-copied');
          copyLabel.textContent = 'Copiar';
        }, 2000);
      });
    });
  });

  pauseButton.addEventListener('click', function () {
    paused = !paused;
    pauseButton.setAttribute('aria-pressed', String(paused));
    pauseButton.textContent = paused ? 'Reanudar' : 'Pausar';
    scheduleNext();
  });

  document.addEventListener('visibilitychange', scheduleNext);
  panel.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      event.preventDefault();
      closePanel();
    } else if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
      event.preventDefault();
      showSlide(current + (event.key === 'ArrowRight' ? 1 : -1), true);
    }
  });
})();
