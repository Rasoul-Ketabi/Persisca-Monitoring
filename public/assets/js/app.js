(function () {
  'use strict';
  var body = document.body;
  document.querySelectorAll('[data-open-sidebar]').forEach(function (el) {
    el.addEventListener('click', function () { body.classList.add('sidebar-open'); });
  });
  document.querySelectorAll('[data-close-sidebar]').forEach(function (el) {
    el.addEventListener('click', function () { body.classList.remove('sidebar-open'); });
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') body.classList.remove('sidebar-open');
  });
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.getAttribute('data-confirm') || 'Are you sure?')) event.preventDefault();
    });
  });
  document.querySelectorAll('input[type="file"]').forEach(function (input) {
    input.addEventListener('change', function () {
      var label = input.parentNode.querySelector('[data-file-label]');
      if (label) label.textContent = input.files && input.files[0] ? input.files[0].name : 'Choose file';
    });
  });
})();
