(function() {
  'use strict';

  var modal = document.getElementById('confirmActionModal');
  var title = document.getElementById('confirmActionTitle');
  var message = document.getElementById('confirmActionMessage');
  var button = document.getElementById('confirmActionButton');
  var onConfirm = null;

  if (!modal || !title || !message || !button) return;

  window.confirmAction = function(options) {
    title.textContent = options.title || 'Confirmar acción';
    message.textContent = options.message || '';
    button.textContent = options.confirmLabel || 'Confirmar';
    onConfirm = typeof options.onConfirm === 'function' ? options.onConfirm : null;
    modal.classList.add('modal--open');
    button.focus();
  };

  button.addEventListener('click', function() {
    modal.classList.remove('modal--open');
    if (onConfirm) onConfirm();
    onConfirm = null;
  });
})();
