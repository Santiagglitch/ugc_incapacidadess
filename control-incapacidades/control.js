(() => {
  'use strict';

  const form = document.querySelector('[data-control-form]');
  if (!form) return;

  const switches = Array.from(form.querySelectorAll('input[type="checkbox"][data-original]'));
  const reauth = form.querySelector('[data-reauth]');
  const status = form.querySelector('[data-change-status]');
  const confirm = form.querySelector('[data-confirm]');
  const password = form.querySelector('[data-reauth-password]');

  const refresh = () => {
    const changed = switches.some((input) => (input.checked ? '1' : '0') !== input.dataset.original);
    reauth.classList.toggle('is-visible', changed);
    status.textContent = changed ? 'Cambios pendientes de confirmacion' : 'Sin cambios pendientes';
    confirm.required = changed;
    password.required = changed;
  };

  switches.forEach((input) => input.addEventListener('change', refresh));
  refresh();
})();
