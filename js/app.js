/* ========================================
   Hanul Beauty - App JS
   ======================================== */

(function() {
  'use strict';

  // ---- Estado global ----
  var csrfToken = document.querySelector('input[name="csrf_token"]')
    ? document.querySelector('input[name="csrf_token"]').value
    : '';

  // ---- Toast notifications ----
  function ensureToastContainer() {
    var c = document.getElementById('toastContainer');
    if (!c) {
      c = document.createElement('div');
      c.className = 'toast-container';
      c.id = 'toastContainer';
      document.body.appendChild(c);
    }
    return c;
  }

  function showToast(msg, type) {
    var container = ensureToastContainer();
    var toast = document.createElement('div');
    toast.className = 'toast toast--' + (type || 'info');

    var icons = {
      success: '<svg class="toast__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>',
      error:   '<svg class="toast__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
      info:    '<svg class="toast__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
      warning: '<svg class="toast__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01"/></svg>'
    };

    toast.innerHTML = (icons[type] || icons.info) +
      '<span class="toast__text">' + msg + '</span>' +
      '<button class="toast__close" aria-label="Cerrar">&times;</button>';

    container.appendChild(toast);
    requestAnimationFrame(function() { toast.classList.add('toast--visible'); });

    var dismiss = function() {
      toast.classList.remove('toast--visible');
      toast.classList.add('toast--exit');
      setTimeout(function() { toast.remove(); }, 400);
    };

    toast.querySelector('.toast__close').addEventListener('click', dismiss);
    setTimeout(dismiss, 4500);
  }

  // ---- Indicador de navegacion activa ----
  (function() {
    var links = document.querySelectorAll('.nav__link');
    var page = window.location.pathname.split('/').pop() || 'index.php';

    function updateActiveLink() {
      if (page !== 'index.php') return;
      links.forEach(function(link) {
        link.classList.remove('nav__link--active');
        link.removeAttribute('aria-current');
        if (window.location.hash && link.getAttribute('href') === window.location.hash) {
          link.classList.add('nav__link--active');
          link.setAttribute('aria-current', 'page');
        }
      });
    }

    updateActiveLink();
    window.addEventListener('hashchange', updateActiveLink);
  })();

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
        toggle.setAttribute('aria-expanded', dropdown.classList.contains('user-menu__dropdown--open') ? 'true' : 'false');
      });

      document.addEventListener('click', function(e) {
        if (!e.target.closest('#userMenu')) {
          dropdown.classList.remove('user-menu__dropdown--open');
          toggle.setAttribute('aria-expanded', 'false');
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
  var ADMIN_ROLES = [2, 3, 4];

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

    // Dashboard link for admin roles
    if (logged && user && ADMIN_ROLES.indexOf(user.role) !== -1) {
      if (!logged.querySelector('.user-menu__dashboard-link')) {
        var link = document.createElement('a');
        link.href = 'dashboard.php';
        link.className = 'user-menu__item user-menu__dashboard-link';
        link.style.cssText = 'text-decoration:none; display:block;';
        link.textContent = 'Panel de Reservas';
        var div = document.createElement('div');
        div.className = 'user-menu__divider user-menu__dashboard-divider';
        var firstItem = logged.querySelector('.user-menu__item');
        if (firstItem) {
          logged.insertBefore(div, firstItem);
          logged.insertBefore(link, div);
        }
      }
    }
  }

  function setLoggedOut() {
    var guest = document.getElementById('userMenuGuest');
    var logged = document.getElementById('userMenuLogged');
    if (guest) guest.style.display = '';
    if (logged) logged.style.display = 'none';

    var dashLink = logged ? logged.querySelector('.user-menu__dashboard-link') : null;
    var dashDiv = logged ? logged.querySelector('.user-menu__dashboard-divider') : null;
    if (dashLink) dashLink.remove();
    if (dashDiv) dashDiv.remove();
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
          if (data.user && ADMIN_ROLES.indexOf(data.user.role) !== -1) {
            window.location.href = 'dashboard.php';
            return;
          }
          setLoggedIn(data.user);
          var modal = document.getElementById('loginModal');
          if (modal) window.closeModal(modal);
          form.reset();
          showToast('Bienvenido/a, ' + (data.user && data.user.name ? data.user.name : '') + '!', 'success');
        } else {
          showError('loginError', data.error || 'Error al iniciar sesion');
          showToast(data.error || 'Error al iniciar sesion', 'error');
        }
      })
      .catch(function() {
        showError('loginError', 'Error de conexion. Intenta de nuevo.');
        showToast('Error de conexion. Intenta de nuevo.', 'error');
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
          showToast('Cuenta creada exitosamente. Bienvenido/a!', 'success');
        } else {
          showError('registerError', data.error || 'Error al registrarse');
          showToast(data.error || 'Error al registrarse', 'error');
        }
      })
      .catch(function() {
        showError('registerError', 'Error de conexion. Intenta de nuevo.');
        showToast('Error de conexion. Intenta de nuevo.', 'error');
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
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ csrf_token: csrfToken })
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          setLoggedOut();
          var dropdown = document.getElementById('userMenuDropdown');
          if (dropdown) dropdown.classList.remove('user-menu__dropdown--open');
          showToast('Sesion cerrada correctamente', 'info');
        }
      })
      .catch(function() {
        setLoggedOut();
        showToast('Sesion cerrada', 'info');
      });
    });
  })();

  // ---- Booking (crear reserva) ----
  (function() {
    var confirmBtn = document.getElementById('confirmBookingBtn');
    var messageEl = document.getElementById('bookingMessage');
    if (!confirmBtn) return;

    var selectServ = document.getElementById('tratamiento');
    var fechaInput = document.getElementById('fecha');
    var horaSelect = document.getElementById('hora');
    var today = new Date().toISOString().split('T')[0];

    if (fechaInput) {
      fechaInput.setAttribute('min', today);
      if (!fechaInput.value) fechaInput.value = today;
    }

    function getSelectedEsteticista() {
      var checked = document.querySelector('input[name="esteticista_id"]:checked');
      return checked ? parseInt(checked.value) : 0;
    }

    // ---- Cargar horarios disponibles ----
    var loadingSlots = false;
    function loadAvailableSlots() {
      if (!fechaInput || !fechaInput.value || !selectServ) return;
      if (loadingSlots) return;
      loadingSlots = true;

      var estId = getSelectedEsteticista();
      var params = 'date=' + fechaInput.value +
                   '&servicio_id=' + selectServ.value +
                   '&esteticista_id=' + estId;

      horaSelect.innerHTML = '<option value="">Cargando...</option>';
      horaSelect.disabled = true;

      fetch('api/appointments/availability.php?' + params)
        .then(function(r) { return r.json(); })
        .then(function(data) {
          loadingSlots = false;
          horaSelect.disabled = false;

          if (!data.success || data.slots.length === 0) {
            horaSelect.innerHTML = '<option value="">Sin horarios disponibles</option>';
            confirmBtn.disabled = true;
            return;
          }

          horaSelect.innerHTML = data.slots.map(function(s) {
            return '<option value="' + s + '">' + s + '</option>';
          }).join('');
          confirmBtn.disabled = false;
        })
        .catch(function() {
          loadingSlots = false;
          horaSelect.disabled = false;
          horaSelect.innerHTML = '<option value="">Error al cargar</option>';
        });
    }

    if (selectServ) selectServ.addEventListener('change', loadAvailableSlots);
    if (fechaInput) fechaInput.addEventListener('change', loadAvailableSlots);

    document.querySelectorAll('input[name="esteticista_id"]').forEach(function(radio) {
      radio.addEventListener('change', loadAvailableSlots);
    });

    loadAvailableSlots();

    // ---- Enviar reserva ----
    confirmBtn.addEventListener('click', function() {
      var nombre = document.getElementById('bookNombre');
      var correo = document.getElementById('bookCorreo');
      var telefono = document.getElementById('bookTelefono');

      if (!nombre || !nombre.value.trim()) {
        showToast('Por favor ingresa tu nombre.', 'warning');
        return;
      }
      if (!correo || !correo.value.trim()) {
        showToast('Por favor ingresa tu correo.', 'warning');
        return;
      }
      if (!fechaInput || !fechaInput.value) {
        showToast('Por favor selecciona una fecha.', 'warning');
        return;
      }
      if (!horaSelect.value) {
        showToast('No hay horario disponible seleccionado.', 'warning');
        return;
      }

      confirmBtn.disabled = true;
      confirmBtn.textContent = 'Agendando...';

      var body = {
        nombre: nombre.value.trim(),
        correo: correo.value.trim(),
        telefono: telefono ? telefono.value.trim() : '',
        servicio_id: parseInt(selectServ.value),
        esteticista_id: getSelectedEsteticista(),
        date: fechaInput.value,
        time: horaSelect.value,
        csrf_token: csrfToken
      };

      fetch('api/appointments/create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirmar Reserva';

        if (data.success) {
          showToast('Cita agendada con exito. Te contactaremos para confirmar.', 'success');
          if (messageEl) {
            messageEl.textContent = 'Cita agendada. Revisa tu correo para completar tu cuenta.';
            messageEl.style.color = '#27ae60';
          }
          loadAvailableSlots();
        } else {
          showToast(data.error || 'Error al agendar la cita.', 'error');
          if (messageEl) {
            messageEl.textContent = data.error || 'Error al agendar la cita.';
            messageEl.style.color = '#e74c3c';
          }
        }
      })
      .catch(function() {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirmar Reserva';
        showToast('Error de conexion. Intenta de nuevo.', 'error');
      });
    });
  })();

  // ---- Actualizar todos los tokens CSRF en el DOM ----
  function updateAllCsrfTokens(token) {
    document.querySelectorAll('input[name="csrf_token"]').forEach(function(input) {
      input.value = token;
    });
  }

  function escHtml(str) {
    var d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
  }

  // ---- Mis citas ----
  (function() {
    var btn = document.getElementById('openCitasBtn');
    if (!btn) return;

    btn.addEventListener('click', loadCitas);

    function loadCitas() {
      var container = document.getElementById('citasList');
      container.innerHTML = '<p style="text-align:center; color:#999;">Cargando...</p>';

      fetch('api/appointments/list.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.success || data.reservas.length === 0) {
            container.innerHTML = '<p class="modal__empty">No tienes citas agendadas.</p>';
            return;
          }

          container.innerHTML = data.reservas.map(function(r) {
            var fecha = new Date(r.fecha_hora_inicio);
            var fechaStr = fecha.toLocaleDateString('es-CO', { day: '2-digit', month: 'long', year: 'numeric' });
            var horaStr = fecha.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', hour12: true });
            var estadoCls = {
              'Pendiente': 'pendiente', 'Confirmada': 'confirmada', 'Completada': 'completada',
              'Cancelada': 'cancelada', 'Reasignada': 'reasignada', 'No_Show': 'no_show'
            };
            var cls = estadoCls[r.nombre_estado] || 'pendiente';

            var cancelBtn = r.nombre_estado === 'Pendiente'
              ? '<div class="cita-card__actions"><button class="btn--danger" data-cancel-cita="' + r.id_reserva + '">Cancelar</button></div>'
              : '';

            return '<div class="cita-card">' +
              '<div class="cita-card__header">' +
                '<span class="cita-card__servicio">' + escHtml(r.nombre_servicio) + '</span>' +
                '<span class="status-badge status-badge--' + cls + '">' + escHtml(r.nombre_estado) + '</span>' +
              '</div>' +
              '<div class="cita-card__details">' +
                '<span>Fecha: <strong>' + fechaStr + '</strong></span>' +
                '<span>Hora: <strong>' + horaStr + '</strong></span>' +
                '<span>Especialista: <strong>' + escHtml(r.nombre_esteticista) + '</strong></span>' +
                '<span>Duracion: <strong>' + r.duracion_minutos + ' min</strong></span>' +
                '<span>Valor: <strong>$' + Number(r.precio).toLocaleString('es-CO') + '</strong></span>' +
              '</div>' +
              cancelBtn +
            '</div>';
          }).join('');

          container.querySelectorAll('[data-cancel-cita]').forEach(function(b) {
            b.addEventListener('click', function() {
              cancelCita(parseInt(b.dataset.cancelCita));
            });
          });
        })
        .catch(function() {
          container.innerHTML = '<p class="modal__empty">Error al cargar tus citas.</p>';
        });
    }

    function cancelCita(id) {
      fetch('api/appointments/cancel.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ reserva_id: id, csrf_token: csrfToken })
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          showToast('Cita cancelada', 'info');
          loadCitas();
        } else {
          showToast(data.error || 'No se pudo cancelar', 'error');
        }
      })
      .catch(function() { showToast('Error de conexion', 'error'); });
    }
  })();

  // ---- Mi perfil ----
  (function() {
    var btn = document.getElementById('openPerfilBtn');
    var form = document.getElementById('perfilForm');
    if (!btn || !form) return;

    btn.addEventListener('click', function() {
      fetch('api/auth/profile.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.success) {
            document.getElementById('perfilNombre').value = data.user.name;
            document.getElementById('perfilCorreo').value = data.user.email;
            document.getElementById('perfilTelefono').value = data.user.phone || '';
            document.getElementById('perfilPassActual').value = '';
            document.getElementById('perfilPassNueva').value = '';
          }
        });
    });

    form.addEventListener('submit', function(e) {
      e.preventDefault();

      var body = {
        name: document.getElementById('perfilNombre').value.trim(),
        phone: document.getElementById('perfilTelefono').value.trim(),
        current_password: document.getElementById('perfilPassActual').value,
        new_password: document.getElementById('perfilPassNueva').value,
        csrf_token: csrfToken
      };

      fetch('api/auth/profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          showToast('Perfil actualizado', 'success');
          var modal = document.getElementById('perfilModal');
          if (modal) window.closeModal(modal);
          var nameEl = document.querySelector('.user-menu__name');
          if (nameEl) nameEl.textContent = body.name;
        } else {
          showError('perfilError', data.error || 'Error al guardar');
          showToast(data.error || 'Error al guardar', 'error');
        }
      })
      .catch(function() {
        showToast('Error de conexion', 'error');
      });
    });
  })();

  // ---- Favoritos ----
  (function() {
    var btn = document.getElementById('openFavoritosBtn');
    if (!btn) return;

    btn.addEventListener('click', loadFavoritos);

    function loadFavoritos() {
      var container = document.getElementById('favoritosList');
      container.innerHTML = '<p style="text-align:center; color:#999;">Cargando...</p>';

      fetch('api/favorites/list.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.success || data.favoritos.length === 0) {
            container.innerHTML = '<p class="modal__empty">No tienes tratamientos favoritos.<br>Usa el corazon en las tarjetas de tratamientos para agregar.</p>';
            return;
          }

          container.innerHTML = data.favoritos.map(function(f) {
            return '<div class="fav-card">' +
              '<div class="fav-card__info">' +
                '<div class="fav-card__name">' + escHtml(f.nombre_servicio) + '</div>' +
                '<div class="fav-card__meta">' + escHtml(f.nombre_categoria) + ' &bull; ' + f.duracion_minutos + ' min &bull; $' + Number(f.precio).toLocaleString('es-CO') + '</div>' +
              '</div>' +
              '<button class="fav-card__remove" data-remove-fav="' + f.id_servicio + '" title="Quitar de favoritos">&times;</button>' +
            '</div>';
          }).join('');

          container.querySelectorAll('[data-remove-fav]').forEach(function(b) {
            b.addEventListener('click', function() {
              toggleFav(parseInt(b.dataset.removeFav));
            });
          });
        })
        .catch(function() {
          container.innerHTML = '<p class="modal__empty">Error al cargar favoritos.</p>';
        });
    }

    function toggleFav(servicioId) {
      fetch('api/favorites/toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ servicio_id: servicioId, csrf_token: csrfToken })
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          showToast(data.message, data.added ? 'success' : 'info');
          loadFavoritos();
          updateFavButtons();
        } else {
          showToast(data.error || 'Error', 'error');
        }
      })
      .catch(function() { showToast('Error de conexion', 'error'); });
    }

    window.toggleFav = toggleFav;
    window.loadFavoritos = loadFavoritos;
  })();

  // ---- Botones de favorito en tarjetas de tratamientos ----
  function updateFavButtons() {
    fetch('api/favorites/list.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success) return;
        var favIds = data.favoritos.map(function(f) { return f.id_servicio; });
        document.querySelectorAll('.treatment-card__fav').forEach(function(btn) {
          var sid = parseInt(btn.dataset.servicioId);
          btn.classList.toggle('treatment-card__fav--active', favIds.indexOf(sid) !== -1);
        });
      });
  }

  document.addEventListener('click', function(e) {
    var favBtn = e.target.closest('.treatment-card__fav');
    if (!favBtn) return;
    var sid = parseInt(favBtn.dataset.servicioId);
    if (!sid) return;
    if (window.toggleFav) {
      window.toggleFav(sid);
    } else {
      showToast('Inicia sesion para guardar favoritos', 'warning');
    }
  });

})();
