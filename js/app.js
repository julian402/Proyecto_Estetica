/* ========================================
   Hanul Beauty - App JS
   ======================================== */

(function() {
  'use strict';

  // ---- Estado global ----
  var csrfToken = document.querySelector('input[name="csrf_token"]')
    ? document.querySelector('input[name="csrf_token"]').value
    : '';

  // ---- Carousel 3D ----
  (function() {
    var carousel = document.getElementById('heroCarousel');
    if (!carousel) return;

    var cards = carousel.querySelectorAll('.carousel-3d__card');
    var total = cards.length;
    var current = 0;

    function update() {
      cards.forEach(function(card, i) {
        var offset = i - current;
        if (offset > Math.floor(total / 2)) offset -= total;
        if (offset < -Math.floor(total / 2)) offset += total;

        var absOffset = Math.abs(offset);
        var translateX = offset * 70;
        var translateZ = -absOffset * 120;
        var scale = 1 - absOffset * 0.15;
        var opacity = absOffset > 2 ? 0 : 1 - absOffset * 0.3;
        var zIndex = total - absOffset;

        card.style.transform =
          'translateX(' + translateX + '%) translateZ(' + translateZ + 'px) scale(' + Math.max(scale, 0.6) + ')';
        card.style.opacity = Math.max(opacity, 0);
        card.style.zIndex = zIndex;
        card.style.filter = absOffset === 0 ? 'none' : 'brightness(0.85)';
        card.style.pointerEvents = absOffset === 0 ? 'auto' : 'none';
      });
    }

    update();

    setInterval(function() {
      current = (current + 1) % total;
      update();
    }, 3000);

    carousel.addEventListener('click', function(e) {
      var card = e.target.closest('.carousel-3d__card');
      if (!card) return;
      var idx = Array.from(cards).indexOf(card);
      if (idx !== current) {
        current = idx;
        update();
      }
    });
  })();

  // ---- Specialist Pills ----
  (function() {
    var pills = document.querySelectorAll('.specialist-pill');
    var summaryRows = document.querySelectorAll('.booking__summary-row');
    if (summaryRows.length < 3) return;

    var summaryEsp = summaryRows[2].querySelector('strong');

    pills.forEach(function(pill) {
      pill.addEventListener('click', function() {
        pills.forEach(function(p) { p.classList.remove('specialist-pill--active'); });
        pill.classList.add('specialist-pill--active');
        var input = pill.querySelector('input');
        // Mostrar nombre: si value es "0" es aleatorio, si no es el texto del label
        if (input.value === '0') {
          if (summaryEsp) summaryEsp.textContent = 'Aleatorio (disponible)';
        } else {
          // Obtener texto del pill (sin el dot span)
          var name = pill.textContent.trim();
          if (summaryEsp) summaryEsp.textContent = name;
        }
      });
    });

    var checked = document.querySelector('.specialist-pill input:checked');
    if (checked) checked.closest('.specialist-pill').classList.add('specialist-pill--active');
  })();

  // ---- Treatment select -> booking summary update ----
  (function() {
    var select = document.getElementById('tratamiento');
    if (!select) return;

    var summaryRows = document.querySelectorAll('.booking__summary-row');
    if (summaryRows.length < 4) return;

    var summaryTreatment = summaryRows[0].querySelector('strong');
    var summaryDuration  = summaryRows[1].querySelector('strong');
    var summaryPrice     = summaryRows[3].querySelector('strong');

    function updateSummary() {
      var opt = select.options[select.selectedIndex];
      if (!opt) return;
      var parts = opt.textContent.split(' — ');
      if (summaryTreatment) summaryTreatment.textContent = parts[0].trim();
      if (summaryDuration) summaryDuration.textContent = opt.getAttribute('data-duracion') + ' min';
      if (summaryPrice) summaryPrice.textContent = '$' + opt.getAttribute('data-precio');
    }

    select.addEventListener('change', updateSummary);

    // Treatment card -> booking pre-selection
    document.querySelectorAll('[data-treatment]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var idx = parseInt(btn.getAttribute('data-treatment'));
        select.selectedIndex = idx;
        updateSummary();
      });
    });
  })();

  // ---- Filter tabs (tratamientos) ----
  (function() {
    var tabs = document.querySelectorAll('.filter-tab');
    var cards = document.querySelectorAll('.treatment-card');

    tabs.forEach(function(tab) {
      tab.addEventListener('click', function() {
        tabs.forEach(function(t) { t.classList.remove('filter-tab--active'); });
        tab.classList.add('filter-tab--active');

        var filter = tab.getAttribute('data-filter');
        cards.forEach(function(card) {
          if (filter === 'todos' || filter === 'populares') {
            card.style.display = '';
          } else {
            card.style.display = card.getAttribute('data-category') === filter ? '' : 'none';
          }
        });
      });
    });
  })();

  // ---- User Menu & Modals ----
  (function() {
    var toggle = document.getElementById('userMenuToggle');
    var dropdown = document.getElementById('userMenuDropdown');

    if (toggle && dropdown) {
      toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('user-menu__dropdown--open');
      });

      document.addEventListener('click', function(e) {
        if (!e.target.closest('#userMenu')) {
          dropdown.classList.remove('user-menu__dropdown--open');
        }
      });
    }

    function openModal(id) {
      if (dropdown) dropdown.classList.remove('user-menu__dropdown--open');
      var modal = document.getElementById(id);
      if (modal) {
        modal.classList.add('modal--open');
        document.body.style.overflow = 'hidden';
      }
    }

    function closeModal(modal) {
      modal.classList.remove('modal--open');
      document.body.style.overflow = '';
      var err = modal.querySelector('.modal__error');
      if (err) err.style.display = 'none';
    }

    document.querySelectorAll('[data-open-modal]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        openModal(btn.getAttribute('data-open-modal'));
      });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        closeModal(btn.closest('.modal'));
      });
    });

    document.querySelectorAll('[data-switch-modal]').forEach(function(link) {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        closeModal(link.closest('.modal'));
        openModal(link.getAttribute('data-switch-modal'));
      });
    });

    document.querySelectorAll('.modal').forEach(function(modal) {
      modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal(modal);
      });
    });

    window.openModal = openModal;
    window.closeModal = closeModal;
  })();

  // ---- Alternar UI guest/logged ----
  function setLoggedIn(user) {
    var guest = document.getElementById('userMenuGuest');
    var logged = document.getElementById('userMenuLogged');
    if (guest) guest.style.display = 'none';
    if (logged) logged.style.display = '';

    var nameEl = logged ? logged.querySelector('.user-menu__name') : null;
    if (nameEl && user && user.name) {
      nameEl.textContent = user.name;
    } else if (!nameEl && logged && user && user.name) {
      var span = document.createElement('span');
      span.className = 'user-menu__name';
      span.style.cssText = 'padding: 8px 16px; font-weight: 500; color: var(--color-text);';
      span.textContent = user.name;
      var divider = document.createElement('div');
      divider.className = 'user-menu__divider';
      logged.insertBefore(divider, logged.firstChild);
      logged.insertBefore(span, logged.firstChild);
    }
  }

  function setLoggedOut() {
    var guest = document.getElementById('userMenuGuest');
    var logged = document.getElementById('userMenuLogged');
    if (guest) guest.style.display = '';
    if (logged) logged.style.display = 'none';
  }

  function showError(elementId, message) {
    var el = document.getElementById(elementId);
    if (el) {
      el.textContent = message;
      el.style.display = 'block';
    }
  }

  // ---- Login ----
  (function() {
    var form = document.getElementById('loginForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
      e.preventDefault();

      var email = document.getElementById('loginCorreo').value.trim();
      var password = document.getElementById('loginPassword').value;

      fetch('api/auth/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: email,
          password: password,
          csrf_token: csrfToken
        })
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          if (data.csrf_token) csrfToken = data.csrf_token;
          updateAllCsrfTokens(csrfToken);
          setLoggedIn(data.user);
          var modal = document.getElementById('loginModal');
          if (modal) window.closeModal(modal);
          form.reset();
        } else {
          showError('loginError', data.error || 'Error al iniciar sesion');
        }
      })
      .catch(function() {
        showError('loginError', 'Error de conexion. Intenta de nuevo.');
      });
    });
  })();

  // ---- Register ----
  (function() {
    var form = document.getElementById('registerForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
      e.preventDefault();

      var name = document.getElementById('regNombre').value.trim();
      var email = document.getElementById('regCorreo').value.trim();
      var password = document.getElementById('regPassword').value;
      var confirm = document.getElementById('regPasswordConfirm').value;

      if (password !== confirm) {
        showError('registerError', 'Las contrasenas no coinciden');
        return;
      }

      fetch('api/auth/register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: name,
          email: email,
          password: password,
          password_confirm: confirm,
          csrf_token: csrfToken
        })
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          if (data.csrf_token) csrfToken = data.csrf_token;
          updateAllCsrfTokens(csrfToken);
          setLoggedIn(data.user);
          var modal = document.getElementById('registerModal');
          if (modal) window.closeModal(modal);
          form.reset();
        } else {
          showError('registerError', data.error || 'Error al registrarse');
        }
      })
      .catch(function() {
        showError('registerError', 'Error de conexion. Intenta de nuevo.');
      });
    });
  })();

  // ---- Logout ----
  (function() {
    var btn = document.getElementById('logoutBtn');
    if (!btn) return;

    btn.addEventListener('click', function() {
      fetch('api/auth/logout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          setLoggedOut();
          var dropdown = document.getElementById('userMenuDropdown');
          if (dropdown) dropdown.classList.remove('user-menu__dropdown--open');
        }
      })
      .catch(function() {
        setLoggedOut();
      });
    });
  })();

  // ---- Booking (crear reserva) ----
  (function() {
    var confirmBtn = document.getElementById('confirmBookingBtn');
    var messageEl = document.getElementById('bookingMessage');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', function() {
      var select = document.getElementById('tratamiento');
      var fecha = document.getElementById('fecha');
      var hora = document.getElementById('hora');
      var esteticista = document.querySelector('input[name="esteticista_id"]:checked');

      if (!fecha || !fecha.value) {
        if (messageEl) {
          messageEl.textContent = 'Por favor selecciona una fecha.';
          messageEl.style.color = '#e74c3c';
        }
        return;
      }

      var body = {
        servicio_id: parseInt(select.value),
        esteticista_id: esteticista ? parseInt(esteticista.value) : 0,
        date: fecha.value,
        time: hora.value,
        csrf_token: csrfToken
      };

      fetch('api/appointments/create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          if (messageEl) {
            messageEl.textContent = 'Cita agendada con exito. Te contactaremos para confirmar.';
            messageEl.style.color = '#27ae60';
          }
        } else {
          if (data.error && data.error.indexOf('sesion') !== -1) {
            window.openModal('loginModal');
          }
          if (messageEl) {
            messageEl.textContent = data.error || 'Error al agendar la cita.';
            messageEl.style.color = '#e74c3c';
          }
        }
      })
      .catch(function() {
        if (messageEl) {
          messageEl.textContent = 'Error de conexion. Intenta de nuevo.';
          messageEl.style.color = '#e74c3c';
        }
      });
    });
  })();

  // ---- Actualizar todos los tokens CSRF en el DOM ----
  function updateAllCsrfTokens(token) {
    document.querySelectorAll('input[name="csrf_token"]').forEach(function(input) {
      input.value = token;
    });
  }

})();
