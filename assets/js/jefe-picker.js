(function () {
  function getOptions(select) {
    return Array.prototype.slice.call(select.options)
      .filter(function (option) {
        return option.value !== '';
      })
      .map(function (option) {
        return {
          value: option.value,
          label: option.textContent.trim()
        };
      });
  }

  function closeOtherPickers(current) {
    document.querySelectorAll('.jefe-picker.is-open').forEach(function (picker) {
      if (picker !== current) {
        picker.classList.remove('is-open');
        var panel = picker.querySelector('.jefe-picker__panel');
        var trigger = picker.querySelector('.jefe-picker__trigger');
        if (panel) panel.hidden = true;
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
      }
    });
  }

  window.initJefePicker = function (select) {
    if (!select || select.dataset.jefePickerReady === '1') {
      return null;
    }

    var options = getOptions(select);
    var placeholder = select.options[0] ? select.options[0].textContent.trim() : 'Selecciona...';
    var wrapper = document.createElement('div');
    var trigger = document.createElement('button');
    var triggerText = document.createElement('span');
    var panel = document.createElement('div');
    var searchWrap = document.createElement('div');
    var search = document.createElement('input');
    var list = document.createElement('div');

    select.dataset.jefePickerReady = '1';
    select.classList.add('jefe-picker__native');
    select.dataset.wasRequired = select.hasAttribute('required') ? '1' : '0';
    select.removeAttribute('required');

    wrapper.className = 'jefe-picker jefe-picker--ready';
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(select);

    trigger.type = 'button';
    trigger.className = 'jefe-picker__trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.appendChild(triggerText);

    panel.className = 'jefe-picker__panel';
    panel.hidden = true;

    searchWrap.className = 'jefe-picker__search-wrap';
    search.type = 'search';
    search.className = 'jefe-picker__search';
    search.placeholder = 'Buscar jefe';
    search.autocomplete = 'off';
    searchWrap.appendChild(search);

    list.className = 'jefe-picker__list';
    list.setAttribute('role', 'listbox');

    panel.appendChild(searchWrap);
    panel.appendChild(list);
    wrapper.appendChild(trigger);
    wrapper.appendChild(panel);

    function selectedOption() {
      return select.options[select.selectedIndex] || null;
    }

    function syncTrigger() {
      var option = selectedOption();
      var hasValue = option && option.value !== '';
      triggerText.textContent = hasValue ? option.textContent.trim() : placeholder;
      trigger.classList.toggle('is-placeholder', !hasValue);
    }

    function close() {
      wrapper.classList.remove('is-open');
      panel.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
    }

    function render(filter) {
      var term = (filter || '').trim().toLowerCase();
      var visible = options.filter(function (option) {
        return option.label.toLowerCase().indexOf(term) !== -1;
      });

      list.innerHTML = '';

      if (visible.length === 0) {
        var empty = document.createElement('div');
        empty.className = 'jefe-picker__empty';
        empty.textContent = 'No hay coincidencias';
        list.appendChild(empty);
        return;
      }

      visible.forEach(function (option) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'jefe-picker__option';
        button.textContent = option.label;
        button.dataset.value = option.value;
        button.setAttribute('role', 'option');
        button.setAttribute('aria-selected', select.value === option.value ? 'true' : 'false');
        button.classList.toggle('is-selected', select.value === option.value);
        list.appendChild(button);
      });
    }

    function open() {
      closeOtherPickers(wrapper);
      render('');
      search.value = '';
      wrapper.classList.add('is-open');
      panel.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      window.setTimeout(function () {
        search.focus();
      }, 0);
    }

    function choose(value) {
      select.value = value;
      select.dispatchEvent(new Event('change', { bubbles: true }));
      syncTrigger();
      close();
      trigger.focus();
    }

    function focusOption(delta) {
      var buttons = Array.prototype.slice.call(list.querySelectorAll('.jefe-picker__option'));
      if (buttons.length === 0) return;

      var activeIndex = buttons.indexOf(document.activeElement);
      var nextIndex = activeIndex < 0 ? 0 : activeIndex + delta;

      if (nextIndex < 0) nextIndex = buttons.length - 1;
      if (nextIndex >= buttons.length) nextIndex = 0;

      buttons[nextIndex].focus();
    }

    trigger.addEventListener('click', function () {
      if (panel.hidden) {
        open();
      } else {
        close();
      }
    });

    trigger.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        open();
      }
    });

    search.addEventListener('input', function () {
      render(search.value);
    });

    search.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        close();
        trigger.focus();
      }

      if (event.key === 'ArrowDown') {
        event.preventDefault();
        focusOption(1);
      }
    });

    list.addEventListener('click', function (event) {
      var option = event.target.closest('.jefe-picker__option');
      if (option) {
        choose(option.dataset.value);
      }
    });

    list.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        if (event.target.classList.contains('jefe-picker__option')) {
          choose(event.target.dataset.value);
        }
      }

      if (event.key === 'ArrowDown') {
        event.preventDefault();
        focusOption(1);
      }

      if (event.key === 'ArrowUp') {
        event.preventDefault();
        focusOption(-1);
      }

      if (event.key === 'Escape') {
        close();
        trigger.focus();
      }
    });

    document.addEventListener('click', function (event) {
      if (!wrapper.contains(event.target)) {
        close();
      }
    });

    select.addEventListener('change', syncTrigger);
    syncTrigger();

    return {
      select: select,
      focus: function () {
        open();
      },
      isValid: function () {
        return select.value !== '';
      }
    };
  };
})();
