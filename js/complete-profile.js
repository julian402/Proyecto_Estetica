(function () {
  var form = document.getElementById('completeProfileForm');
  if (!form) return;
  form.addEventListener('submit', function (event) {
    event.preventDefault();
    var password = document.getElementById('completePassword').value;
    var confirm = document.getElementById('completePasswordConfirm').value;
    var error = document.getElementById('completeProfileError');
    if (password.length < 8 || password !== confirm) {
      error.textContent = password.length < 8 ? 'La contraseña debe tener al menos 8 caracteres.' : 'Las contraseñas no coinciden.';
      error.hidden = false;
      return;
    }
    fetch('api/auth/complete-profile.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email: document.getElementById('completeEmail').value, password: password, password_confirm: confirm, csrf_token: document.getElementById('completeCsrf').value }) })
      .then(function (response) { return response.json(); })
      .then(function (data) { if (data.success) window.location.replace('cuenta.php'); else { error.textContent = data.error || 'No fue posible activar tu cuenta.'; error.hidden = false; } })
      .catch(function () { error.textContent = 'Error de conexión. Intenta nuevamente.'; error.hidden = false; });
  });
})();
