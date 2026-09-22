/* ========================================
   Hanul Beauty - App JS
   ======================================== */

(function() {
  'use strict';
  var csrfToken = document.querySelector('input[name="csrf_token"]')
    ? document.querySelector('input[name="csrf_token"]').value
    : '';
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
  function setStepperStep(stepNum) {
    var steps = document.querySelectorAll('.stepper__step');
    steps.forEach(function(step) {
      var num = parseInt(step.getAttribute('data-step') || '1');
      step.classList.remove('stepper__step--active');
      step.classList.remove('stepper__step--completed');
      if (num === stepNum) {
        step.classList.add('stepper__step--active');
      } else if (num < stepNum) {
        step.classList.add('stepper__step--completed');
      }
    });
  }
  function updateBookingSummary() {
    var select = document.getElementById('tratamiento');
    if (!select) return;

    var opt = select.options[select.selectedIndex];
    var summaryTreatment = document.getElementById('summaryTreatment');
    var summaryDuration  = document.getElementById('summaryDuration');
    var summaryPrice     = document.getElementById('summaryPrice');
    var summaryEsp       = document.getElementById('summarySpecialist');
    var summaryFechaHora = document.getElementById('summaryFechaHora');

    // Fallbacks a filas si no están los IDs
    var summaryRows = document.querySelectorAll('.booking__summary-row');
    if (!summaryTreatment && summaryRows.length > 0) summaryTreatment = summaryRows[0].querySelector('strong');
    if (!summaryDuration && summaryRows.length > 1) summaryDuration = summaryRows[1].querySelector('strong');
    if (!summaryEsp && summaryRows.length > 2) summaryEsp = summaryRows[2].querySelector('strong');
    if (!summaryPrice && summaryRows.length > 4) summaryPrice = summaryRows[4].querySelector('strong');

    if (opt && opt.value) {
      var parts = opt.textContent.split(' — ');
      if (summaryTreatment) summaryTreatment.textContent = parts[0].trim();
      var dur = opt.getAttribute('data-duracion');
      if (summaryDuration && dur) summaryDuration.textContent = dur + ' min';
      var precio = opt.getAttribute('data-precio');
      if (summaryPrice && precio) summaryPrice.textContent = '$' + precio;
    }

    // Especialista (radio pills debajo de tratamiento)
    var checkedRadio = document.querySelector('input[name="esteticista_id"]:checked');
    if (checkedRadio && summaryEsp) {
      if (checkedRadio.value === '0') {
        summaryEsp.textContent = 'Aleatorio (disponible)';
      } else {
        var pill = checkedRadio.closest('.specialist-pill');
        summaryEsp.textContent = pill ? pill.textContent.trim() : 'Asignado';
      }
    }

    // Fecha y hora
    var fechaIn = document.getElementById('fecha');
    var horaSel = document.getElementById('hora');
    if (summaryFechaHora) {
      if (fechaIn && fechaIn.value && horaSel && horaSel.value && horaSel.value.indexOf('Selecciona') === -1 && horaSel.value.indexOf('Sin') === -1 && horaSel.value.indexOf('Cargando') === -1) {
        var fParts = fechaIn.value.split('-');
        var fStr = fParts.length === 3 ? (fParts[2] + '/' + fParts[1] + '/' + fParts[0]) : fechaIn.value;
        summaryFechaHora.textContent = fStr + ' • ' + horaSel.value;
      } else if (fechaIn && fechaIn.value) {
        var fParts2 = fechaIn.value.split('-');
        var fStr2 = fParts2.length === 3 ? (fParts2[2] + '/' + fParts2[1] + '/' + fParts2[0]) : fechaIn.value;
        summaryFechaHora.textContent = fStr2 + ' (hora pendiente)';
      } else {
        summaryFechaHora.textContent = 'Por seleccionar';
      }
    }
  }
  (function() {
    var pills = document.querySelectorAll('.specialist-pill');
    pills.forEach(function(pill) {
      pill.addEventListener('click', function() {
        pills.forEach(function(p) { p.classList.remove('specialist-pill--active'); });
        pill.classList.add('specialist-pill--active');
        var input = pill.querySelector('input');
        if (input) {
          input.checked = true;
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        updateBookingSummary();
        if (window.loadAvailableSlots) window.loadAvailableSlots();
      });
    });

    var checked = document.querySelector('.specialist-pill input:checked');
    if (checked) checked.closest('.specialist-pill').classList.add('specialist-pill--active');
  })();
  (function() {
    var select = document.getElementById('tratamiento');
    if (select) {
      select.addEventListener('change', function() {
        updateBookingSummary();
        if (window.loadAvailableSlots) window.loadAvailableSlots();
      });
    }

    // Treatment card -> booking pre-selection
    document.querySelectorAll('[data-treatment]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var idx = parseInt(btn.getAttribute('data-treatment'));
        if (select) {
          var validOptions = Array.from(select.options).filter(function(o) { return !o.disabled; });
          if (validOptions[idx]) {
            select.value = validOptions[idx].value;
          } else {
            select.selectedIndex = Math.min(idx + 1, select.options.length - 1);
          }
          updateBookingSummary();
          if (window.loadAvailableSlots) window.loadAvailableSlots();
        }
      });
    });

    // Stepper click & step-group focus tracking
    var steps = document.querySelectorAll('.stepper__step');
    steps.forEach(function(step) {
      step.addEventListener('click', function() {
        var num = parseInt(step.getAttribute('data-step') || '1');
        setStepperStep(num);
        if (num === 1) {
          var el1 = document.getElementById('tratamiento');
          if (el1) el1.focus();
        } else if (num === 2) {
          var el2 = document.getElementById('fecha');
          if (el2) el2.focus();
        } else if (num === 3) {
          var el3 = document.getElementById('bookNombre');
          if (el3) el3.focus();
        }
      });
    });

    var g1 = document.getElementById('stepGroup1');
    var g2 = document.getElementById('stepGroup2');
    var g3 = document.getElementById('stepGroup3');
    if (g1) g1.addEventListener('focusin', function() { setStepperStep(1); });
    if (g2) g2.addEventListener('focusin', function() { setStepperStep(2); });
    if (g3) g3.addEventListener('focusin', function() { setStepperStep(3); });
  })();
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

        // Recalcular las paginas del carrusel con las tarjetas visibles
        if (typeof window.refreshTreatmentsCarousel === 'function') {
          window.refreshTreatmentsCarousel();
        }
      });
    });
  })();
  // Avanza de pagina en pagina (3 tarjetas en escritorio, 2 en tablet, 1 en movil).
  // Usa scroll nativo con scroll-snap, asi el gesto tactil funciona sin codigo extra.
  (function() {
    var track = document.getElementById('treatmentsTrack');
    var prev  = document.getElementById('treatPrev');
    var next  = document.getElementById('treatNext');
    var dots  = document.getElementById('treatmentsDots');
    var nav   = document.getElementById('treatmentsNav');
    if (!track) return;

    // Pagina a la que se esta yendo. Se guarda aparte porque leer scrollLeft
    // a mitad de la animacion suave devuelve una pagina intermedia y el
    // segundo clic seguido no avanzaba.
    var targetPage = 0;

    function visibleCards() {
      return Array.prototype.filter.call(track.children, function(card) {
        return card.offsetParent !== null;
      });
    }

    function perPage() {
      var cards = visibleCards();
      if (!cards.length) return 1;
      var cardW = cards[0].getBoundingClientRect().width;
      if (!cardW) return 1;
      var styles = getComputedStyle(track);
      var gap = parseFloat(styles.columnGap || styles.gap) || 0;
      return Math.max(1, Math.round((track.clientWidth + gap) / (cardW + gap)));
    }

    function pageCount() {
      return Math.max(1, Math.ceil(visibleCards().length / perPage()));
    }

    function currentPage() {
      var cards = visibleCards();
      var pp = perPage();
      for (var i = 0; i < cards.length; i++) {
        if (cards[i].offsetLeft >= track.scrollLeft - 4) {
          return Math.min(pageCount() - 1, Math.floor(i / pp));
        }
      }
      return pageCount() - 1;
    }


    // Desplazamiento a una pagina.
    //
    // La correccion no depende de la animacion: se pide el scroll suave y,
    // si el navegador no lo ejecuta (pestana en segundo plano, motores que
    // lo ignoran), se fija la posicion directamente. Asi el carrusel siempre
    // acaba en la pagina correcta, con animacion o sin ella.
    var comprobacion = null;

    function irAPosicion(destino) {
      clearTimeout(comprobacion);

      var reduceMovimiento = window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      if (reduceMovimiento || typeof track.scrollTo !== 'function') {
        track.scrollLeft = destino;
        return;
      }

      try {
        track.scrollTo({ left: destino, behavior: 'smooth' });
      } catch (err) {
        track.scrollLeft = destino;
        return;
      }

      comprobacion = setTimeout(function () {
        if (Math.abs(track.scrollLeft - destino) > 2) {
          track.scrollLeft = destino;
        }
        syncUI();
      }, 450);
    }

    function goToPage(page) {
      var cards = visibleCards();
      if (!cards.length) return;
      targetPage = Math.max(0, Math.min(pageCount() - 1, page));
      var index = Math.min(cards.length - 1, targetPage * perPage());
      irAPosicion(cards[index].offsetLeft);
      syncUI();
    }

    function syncUI() {
      var total = pageCount();
      // Se usa la pagina objetivo, no el scroll: durante la animacion suave
      // el scroll va por detras y los puntos parpadearian.
      var page = Math.max(0, Math.min(total - 1, targetPage));

      // Con una sola pagina no hace falta paginacion
      if (nav)  nav.style.display  = total <= 1 ? 'none' : 'flex';
      if (dots) dots.style.display = total <= 1 ? 'none' : 'flex';

      if (prev) prev.disabled = page <= 0;
      if (next) next.disabled = page >= total - 1;

      var contador = dots && dots.querySelector('.carousel__count');
      if (contador) {
        contador.textContent = (page + 1) + ' / ' + total;
      } else if (dots) {
        Array.prototype.forEach.call(dots.children, function(dot, i) {
          dot.classList.toggle('carousel__dot--active', i === page);
          if (i === page) {
            dot.setAttribute('aria-current', 'true');
          } else {
            dot.removeAttribute('aria-current');
          }
        });
      }
    }

    // A partir de este numero de paginas una fila de puntos ocupa demasiado
    // (pasa en movil, donde solo cabe una tarjeta por pagina): se muestra
    // un contador compacto.
    var MAX_PUNTOS = 7;

    function buildDots() {
      if (!dots) return;
      var total = pageCount();
      dots.innerHTML = '';

      if (total > MAX_PUNTOS) {
        dots.classList.add('carousel__dots--contador');
        var contador = document.createElement('span');
        contador.className = 'carousel__count';
        contador.setAttribute('aria-live', 'polite');
        dots.appendChild(contador);
        syncUI();
        return;
      }

      dots.classList.remove('carousel__dots--contador');
      for (var i = 0; i < total; i++) {
        var dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'carousel__dot';
        dot.setAttribute('aria-label', 'Ir al grupo ' + (i + 1) + ' de ' + total);
        dot.dataset.page = i;
        dots.appendChild(dot);
      }
      syncUI();
    }

    if (prev) prev.addEventListener('click', function() { goToPage(targetPage - 1); });
    if (next) next.addEventListener('click', function() { goToPage(targetPage + 1); });

    if (dots) {
      dots.addEventListener('click', function(e) {
        var dot = e.target.closest('.carousel__dot');
        if (dot) goToPage(parseInt(dot.dataset.page, 10));
      });
    }

    // Flechas del teclado cuando el carrusel tiene el foco
    track.addEventListener('keydown', function(e) {
      if (e.key === 'ArrowRight') { e.preventDefault(); goToPage(targetPage + 1); }
      if (e.key === 'ArrowLeft')  { e.preventDefault(); goToPage(targetPage - 1); }
    });

    var pendiente, finScroll;
    track.addEventListener('scroll', function() {
      cancelAnimationFrame(pendiente);
      pendiente = requestAnimationFrame(syncUI);

      // Cuando el desplazamiento se detiene (incluido el gesto tactil)
      // la pagina objetivo vuelve a coincidir con lo que se ve.
      clearTimeout(finScroll);
      finScroll = setTimeout(function() {
        targetPage = currentPage();
        syncUI();
      }, 120);
    });

    var redimension;
    window.addEventListener('resize', function() {
      clearTimeout(redimension);
      redimension = setTimeout(buildDots, 150);
    });

    // Los filtros ocultan tarjetas, asi que hay que recalcular las paginas
    window.refreshTreatmentsCarousel = function() {
      clearTimeout(comprobacion);
      track.scrollLeft = 0;
      targetPage = 0;
      buildDots();
    };

    buildDots();
  })();
  (function() {
    var toggle = document.getElementById('navToggle');
    var panel  = document.getElementById('mobileNav');
    if (!toggle || !panel) return;

    function closeMenu() {
      panel.classList.remove('mobile-nav--open');
      toggle.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('nav-open');
    }

    function openMenu() {
      panel.classList.add('mobile-nav--open');
      toggle.setAttribute('aria-expanded', 'true');
      document.body.classList.add('nav-open');
    }

    toggle.addEventListener('click', function(e) {
      e.stopPropagation();
      if (panel.classList.contains('mobile-nav--open')) {
        closeMenu();
      } else {
        openMenu();
      }
    });

    // Cerrar al navegar
    panel.addEventListener('click', function(e) {
      if (e.target.closest('a')) closeMenu();
    });

    // Cerrar al hacer clic fuera
    document.addEventListener('click', function(e) {
      if (!panel.classList.contains('mobile-nav--open')) return;
      if (e.target.closest('#mobileNav') || e.target.closest('#navToggle')) return;
      closeMenu();
    });

    // Cerrar con Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeMenu();
    });

    // Si se vuelve a escritorio, restablecer el estado
    window.addEventListener('resize', function() {
      if (window.innerWidth > 768) closeMenu();
    });
  })();
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
  var ADMIN_ROLES = [2, 3, 4];

  function autofillBookingForm(user) {
    if (!user) return;
    var nameInput  = document.getElementById('bookNombre');
    var emailInput = document.getElementById('bookCorreo');
    var phoneInput = document.getElementById('bookTelefono');

    var userName  = user.name || user.nombre || '';
    var userEmail = user.email || user.correo || '';
    var userPhone = user.phone || user.telefono || '';

    if (nameInput && userName && !nameInput.value) {
      nameInput.value = userName;
    } else if (nameInput && userName) {
      nameInput.value = userName;
    }

    if (emailInput && userEmail && !emailInput.value) {
      emailInput.value = userEmail;
    } else if (emailInput && userEmail) {
      emailInput.value = userEmail;
    }

    if (phoneInput && userPhone) {
      phoneInput.value = userPhone;
    } else if (phoneInput && !phoneInput.value) {
      fetch('api/auth/profile.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.success && data.user && data.user.phone && phoneInput && !phoneInput.value) {
            phoneInput.value = data.user.phone;
          }
        })
        .catch(function() {});
    }
  }

  function updateAvatar(user) {
    var avatar = document.getElementById('perfilAvatar');
    if (!avatar || !user) return;
    var name = user.name || user.nombre || '';
    if (!name) return;
    var parts = name.trim().split(/\s+/);
    var initials = parts.length > 1
      ? (parts[0][0] + parts[1][0]).toUpperCase()
      : name.substring(0, 2).toUpperCase();
    var span = avatar.querySelector('span');
    if (span) span.textContent = initials;
  }

  function setLoggedIn(user) {
    // Los corazones de favoritos se pintan en servidor, pero al iniciar
    // sesion sin recargar hay que traerlos.
    if (typeof updateFavButtons === 'function') updateFavButtons();

    var guest = document.getElementById('userMenuGuest');
    var logged = document.getElementById('userMenuLogged');
    if (guest) guest.style.display = 'none';
    if (logged) logged.style.display = '';

    var isPersonal = user && ADMIN_ROLES.indexOf(user.role) !== -1;
    var destination = isPersonal ? 'dashboard.php' : 'cuenta.php';
    var destinationLabel = isPersonal ? 'Panel de Reservas' : 'Mi cuenta';

    // La cabecera se renderiza inicialmente para invitado. Al iniciar sesion
    // por AJAX se reemplaza ese boton por el acceso del usuario sin recargar.
    var toggle = document.getElementById('userMenuToggle');
    if (toggle && user && user.name) {
      var userLink = document.createElement('a');
      userLink.id = 'userMenuLink';
      userLink.className = 'user-menu__toggle user-menu__toggle--link';
      userLink.href = destination;
      userLink.title = destinationLabel;
      userLink.setAttribute('aria-label', destinationLabel);

      var avatar = document.createElement('span');
      avatar.className = 'user-menu__avatar';
      avatar.setAttribute('aria-hidden', 'true');
      avatar.textContent = Array.from(user.name.trim())[0].toUpperCase();

      var label = document.createElement('span');
      label.className = 'user-menu__label';
      label.textContent = user.name.trim().split(/\s+/)[0];

      userLink.appendChild(avatar);
      userLink.appendChild(label);
      toggle.replaceWith(userLink);
    }

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

    // Acceso al area correspondiente al usuario.
    if (logged && user) {
      if (!logged.querySelector('.user-menu__account-link')) {
        var link = document.createElement('a');
        link.href = destination;
        link.className = 'user-menu__item user-menu__account-link';
        link.style.cssText = 'text-decoration:none; display:block;';
        link.textContent = destinationLabel;
        var div = document.createElement('div');
        div.className = 'user-menu__divider user-menu__account-divider';
        var firstItem = logged.querySelector('.user-menu__item');
        if (firstItem) {
          logged.insertBefore(div, firstItem);
          logged.insertBefore(link, div);
        }
      }
    }

    // Autocompletar datos del usuario logueado en la reserva
    autofillBookingForm(user);
    updateAvatar(user);
  }

  function setLoggedOut() {
    var guest = document.getElementById('userMenuGuest');
    var logged = document.getElementById('userMenuLogged');
    if (guest) guest.style.display = '';
    if (logged) logged.style.display = 'none';

    var accountLink = logged ? logged.querySelector('.user-menu__account-link') : null;
    var accountDiv = logged ? logged.querySelector('.user-menu__account-divider') : null;
    if (accountLink) accountLink.remove();
    if (accountDiv) accountDiv.remove();
  }

  function showError(elementId, message) {
    var el = document.getElementById(elementId);
    if (el) {
      el.textContent = message;
      el.style.display = 'block';
    }
  }
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
          showToast('¡Bienvenido/a, ' + (data.user && data.user.name ? data.user.name : '') + '!', 'success');
        } else {
          showError('loginError', data.error || 'Error al iniciar sesión');
          showToast(data.error || 'Error al iniciar sesión', 'error');
        }
      })
      .catch(function() {
        showError('loginError', 'Error de conexión. Intenta de nuevo.');
        showToast('Error de conexión. Intenta de nuevo.', 'error');
      });
    });
  })();
  (function() {
    var form = document.getElementById('registerForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
      e.preventDefault();

      var name = document.getElementById('regNombre').value.trim();
      var email = document.getElementById('regCorreo').value.trim();
      var password = document.getElementById('regPassword').value;
      var confirm = document.getElementById('regPasswordConfirm').value;
      var telefonoEl = document.getElementById('regTelefono');
      var telefono = telefonoEl ? telefonoEl.value.trim() : '';

      if (!name) {
        showError('registerError', 'Por favor ingresa tu nombre');
        return;
      }
      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError('registerError', 'Por favor ingresa un correo electrónico válido');
        return;
      }
      if (!telefono || telefono.replace(/[^0-9]/g, '').length < 7) {
        showError('registerError', 'Por favor ingresa un número de teléfono válido');
        showToast('Por favor ingresa un número de teléfono válido', 'error');
        return;
      }
      if (password.length < 8) {
        showError('registerError', 'La contraseña debe tener al menos 8 caracteres');
        showToast('La contraseña debe tener al menos 8 caracteres', 'error');
        return;
      }
      if (password !== confirm) {
        showError('registerError', 'Las contraseñas no coinciden');
        showToast('Las contraseñas no coinciden', 'error');
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
          phone: telefono,
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
          showToast('¡Cuenta creada exitosamente! Bienvenido/a.', 'success');
        } else {
          showError('registerError', data.error || 'Error al registrarse');
          showToast(data.error || 'Error al registrarse', 'error');
        }
      })
      .catch(function() {
        showError('registerError', 'Error de conexión. Intenta de nuevo.');
        showToast('Error de conexión. Intenta de nuevo.', 'error');
      });
    });
  })();
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
          window.location.replace('index.php');
        }
      })
      .catch(function() {
        window.location.replace('index.php');
      });
    });
  })();
  var lastBookingInfo = null;

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
    var loadingSlots = false;
    function loadAvailableSlots() {
      if (!fechaInput || !fechaInput.value || !selectServ || !horaSelect) return;
      if (loadingSlots) return;
      loadingSlots = true;

      var estId = getSelectedEsteticista();
      var servVal = selectServ.value;
      if (!servVal || parseInt(servVal) <= 0) {
        loadingSlots = false;
        return;
      }

      var params = 'date=' + encodeURIComponent(fechaInput.value) +
                   '&servicio_id=' + encodeURIComponent(servVal) +
                   '&esteticista_id=' + encodeURIComponent(estId);

      horaSelect.innerHTML = '<option value="">Cargando horarios...</option>';
      horaSelect.disabled = true;

      fetch('api/appointments/availability.php?' + params)
        .then(function(r) { return r.json(); })
        .then(function(data) {
          loadingSlots = false;
          horaSelect.disabled = false;

          if (!data.success || !data.slots || data.slots.length === 0) {
            horaSelect.innerHTML = '<option value="">Sin horarios disponibles</option>';
            confirmBtn.disabled = true;
            if (typeof updateBookingSummary === 'function') updateBookingSummary();
            return;
          }

          horaSelect.innerHTML = data.slots.map(function(s) {
            return '<option value="' + s + '">' + s + '</option>';
          }).join('');
          confirmBtn.disabled = false;
          if (typeof updateBookingSummary === 'function') updateBookingSummary();
        })
        .catch(function() {
          loadingSlots = false;
          horaSelect.disabled = false;
          horaSelect.innerHTML = '<option value="">Error al cargar horarios</option>';
          if (typeof updateBookingSummary === 'function') updateBookingSummary();
        });
    }
    // Limpia tratamiento, especialista, fecha y hora. Los datos de contacto se
    // conservan si hay sesión iniciada (vienen prellenados desde el servidor).
    function resetBookingForm() {
      // Tratamiento: primera opción seleccionable
      if (selectServ && selectServ.options.length) {
        for (var i = 0; i < selectServ.options.length; i++) {
          if (!selectServ.options[i].disabled) {
            selectServ.selectedIndex = i;
            break;
          }
        }
      }

      // Especialista: "Aleatorio (disponible)"
      var pills = document.querySelectorAll('.specialist-pill');
      for (var p = 0; p < pills.length; p++) {
        pills[p].classList.remove('specialist-pill--active');
      }
      var radioAleatorio = document.querySelector('input[name="esteticista_id"][value="0"]');
      if (radioAleatorio) {
        radioAleatorio.checked = true;
        var pill = radioAleatorio.closest('.specialist-pill');
        if (pill) pill.classList.add('specialist-pill--active');
      }

      // Fecha: hoy (nunca vacía, para que el selector de horas pueda cargar)
      if (fechaInput) fechaInput.value = new Date().toISOString().split('T')[0];

      // Hora: se repuebla con loadAvailableSlots()
      if (horaSelect) horaSelect.innerHTML = '<option value="">Selecciona fecha primero</option>';

      // Datos de contacto: solo se limpian para invitados
      var loggedBox = document.getElementById('userMenuLogged');
      var isUserLogged = loggedBox && loggedBox.style.display !== 'none';
      if (!isUserLogged) {
        ['bookNombre', 'bookCorreo', 'bookTelefono'].forEach(function(id) {
          var el = document.getElementById(id);
          if (el) el.value = '';
        });
      }

      loadAvailableSlots();
      if (typeof updateBookingSummary === 'function') updateBookingSummary();
      if (typeof setStepperStep === 'function') setStepperStep(1);
    }
    window.loadAvailableSlots = loadAvailableSlots;

    if (selectServ) {
      selectServ.addEventListener('change', function() {
        loadAvailableSlots();
        if (typeof updateBookingSummary === 'function') updateBookingSummary();
      });
    }
    if (fechaInput) {
      fechaInput.addEventListener('change', function() {
        loadAvailableSlots();
        if (typeof updateBookingSummary === 'function') updateBookingSummary();
      });
    }
    if (horaSelect) {
      horaSelect.addEventListener('change', function() {
        if (typeof updateBookingSummary === 'function') updateBookingSummary();
      });
    }

    document.querySelectorAll('input[name="esteticista_id"]').forEach(function(radio) {
      radio.addEventListener('change', function() {
        loadAvailableSlots();
        if (typeof updateBookingSummary === 'function') updateBookingSummary();
      });
    });

    loadAvailableSlots();
    if (typeof updateBookingSummary === 'function') updateBookingSummary();
    confirmBtn.addEventListener('click', function() {
      var nombreEl   = document.getElementById('bookNombre');
      var correoEl   = document.getElementById('bookCorreo');
      var telefonoEl = document.getElementById('bookTelefono');

      var nombre   = nombreEl ? nombreEl.value.trim() : '';
      var correo   = correoEl ? correoEl.value.trim() : '';
      var telefono = telefonoEl ? telefonoEl.value.trim() : '';
      var servicioId = selectServ ? parseInt(selectServ.value) : 0;
      var esteticistaId = getSelectedEsteticista();
      var fecha = fechaInput ? fechaInput.value.trim() : '';
      var hora = horaSelect ? horaSelect.value.trim() : '';
      if (!servicioId || isNaN(servicioId) || servicioId <= 0) {
        showToast('Por favor selecciona un tratamiento para tu cita.', 'warning');
        if (selectServ) selectServ.focus();
        if (typeof setStepperStep === 'function') setStepperStep(1);
        return;
      }
      if (!fecha) {
        showToast('Por favor selecciona la fecha de tu cita.', 'warning');
        if (fechaInput) fechaInput.focus();
        if (typeof setStepperStep === 'function') setStepperStep(2);
        return;
      }
      var todayStr = new Date().toISOString().split('T')[0];
      if (fecha < todayStr) {
        showToast('La fecha de la reserva debe ser para hoy o un día futuro.', 'warning');
        if (fechaInput) fechaInput.focus();
        if (typeof setStepperStep === 'function') setStepperStep(2);
        return;
      }
      if (!hora || hora.indexOf('Sin horarios') !== -1 || hora.indexOf('Cargando') !== -1 || hora.indexOf('Selecciona') !== -1) {
        showToast('Por favor selecciona una hora disponible para tu cita.', 'warning');
        if (horaSelect) horaSelect.focus();
        if (typeof setStepperStep === 'function') setStepperStep(2);
        return;
      }
      if (!nombre) {
        showToast('Por favor ingresa tu nombre completo.', 'warning');
        if (nombreEl) nombreEl.focus();
        if (typeof setStepperStep === 'function') setStepperStep(3);
        return;
      }
      if (nombre.length < 3) {
        showToast('Por favor ingresa un nombre válido (mínimo 3 caracteres).', 'warning');
        if (nombreEl) nombreEl.focus();
        if (typeof setStepperStep === 'function') setStepperStep(3);
        return;
      }
      if (!telefono) {
        showToast('Por favor ingresa tu número de teléfono de contacto.', 'warning');
        if (telefonoEl) telefonoEl.focus();
        if (typeof setStepperStep === 'function') setStepperStep(3);
        return;
      }
      var cleanPhone = telefono.replace(/[\s+-]/g, '');
      if (cleanPhone.length < 7 || !/^\d+$/.test(cleanPhone)) {
        showToast('Por favor ingresa un número de teléfono válido (mínimo 7 dígitos).', 'warning');
        if (telefonoEl) telefonoEl.focus();
        if (typeof setStepperStep === 'function') setStepperStep(3);
        return;
      }
      if (!correo) {
        showToast('Por favor ingresa tu correo electrónico.', 'warning');
        if (correoEl) correoEl.focus();
        if (typeof setStepperStep === 'function') setStepperStep(3);
        return;
      }
      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(correo)) {
        showToast('Por favor ingresa un correo electrónico válido (ej: nombre@correo.com).', 'warning');
        if (correoEl) correoEl.focus();
        if (typeof setStepperStep === 'function') setStepperStep(3);
        return;
      }

      confirmBtn.disabled = true;
      confirmBtn.textContent = 'Agendando...';

      var body = {
        nombre: nombre,
        correo: correo,
        telefono: telefono,
        servicio_id: servicioId,
        esteticista_id: esteticistaId,
        date: fecha,
        time: hora,
        csrf_token: csrfToken
      };

      fetch('api/appointments/create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      })
      .then(function(res) {
        return res.json().then(function(data) {
          return { status: res.status, data: data };
        });
      })
      .then(function(result) {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirmar reserva';
        var data = result.data;

        if (result.status === 200 && data.success) {
          showToast('¡Cita agendada con éxito! Te contactaremos para confirmar.', 'success');
          if (typeof setStepperStep === 'function') setStepperStep(3);

          lastBookingInfo = {
            nombre: nombre,
            correo: correo,
            telefono: telefono
          };

          if (messageEl) {
            var isUserLogged = document.getElementById('userMenuLogged') &&
                               document.getElementById('userMenuLogged').style.display !== 'none';
            if (!isUserLogged) {
              messageEl.innerHTML = '<span style="color:#27ae60; font-weight:600;">¡Cita agendada con éxito!</span> ' +
                '¿Deseas <button type="button" id="linkCompletarCuenta" style="color:var(--color-olive); font-weight:600; text-decoration:underline; background:none; border:none; cursor:pointer; padding:0; font-size:inherit;">completar tu cuenta</button> para consultar y reprogramar tu cita en cualquier momento?';

              var compLink = document.getElementById('linkCompletarCuenta');
              if (compLink) {
                compLink.addEventListener('click', function() {
                  openCompleteAccountModal(lastBookingInfo);
                });
              }
            } else {
              messageEl.innerHTML = '<span style="color:#27ae60; font-weight:600;">¡Cita agendada con éxito!</span> ' +
                'Puedes consultarla en <a href="cuenta.php?v=citas" style="color:var(--color-olive); font-weight:600;">Mis citas</a>.';
            }
          }

          resetBookingForm();
        } else {
          // Manejo de conflicto de horario del cliente o del especialista
          var errMsg = data.error || 'Error al agendar la cita.';
          var isConflict = result.status === 409 ||
            errMsg.toLowerCase().indexOf('choque') !== -1 ||
            errMsg.toLowerCase().indexOf('ya tiene una reserva') !== -1 ||
            errMsg.toLowerCase().indexOf('bloqueo de agenda') !== -1 ||
            errMsg.toLowerCase().indexOf('ya tienes una cita agendada') !== -1;

          if (isConflict) {
            var conflictMsg = 'Ya tienes una cita agendada a esa misma hora o el especialista ya no está disponible en ese horario. Por favor elige otro horario.';
            showToast(conflictMsg, 'error');
            if (messageEl) {
              messageEl.innerHTML = '<span style="color:#e74c3c; font-weight:500;">' + conflictMsg + '</span>';
            }
          } else {
            showToast(errMsg, 'error');
            if (messageEl) {
              messageEl.innerHTML = '<span style="color:#e74c3c;">' + escHtml(errMsg) + '</span>';
            }
          }
          loadAvailableSlots();
        }
      })
      .catch(function() {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirmar reserva';
        showToast('Error de conexión. Intenta de nuevo.', 'error');
      });
    });
  })();
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
  (function() {
    var btn = document.getElementById('openCitasBtn');
    if (!btn) return;

    btn.addEventListener('click', loadCitas);

    function loadCitas() {
      var container = document.getElementById('citasList');
      if (!container) return;
      container.innerHTML = '<p style="text-align:center; color:#999; padding:24px 0;">Cargando tus citas...</p>';

      fetch('api/appointments/list.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.success || !data.reservas || data.reservas.length === 0) {
            container.innerHTML = '<div class="modal__empty">' +
              '<p style="margin-bottom:8px; font-weight:500; font-size:1rem; color:var(--color-text);">No tienes citas agendadas actualmente.</p>' +
              '<p style="margin-bottom:20px; font-size:0.88rem; color:var(--color-text-muted);">Descubre nuestros tratamientos faciales y reserva tu momento de cuidado coreano.</p>' +
              '<a href="#agendar" class="btn btn--primary btn--sm" data-close-citas>Agendar una cita</a>' +
            '</div>';
            var closeA = container.querySelector('[data-close-citas]');
            if (closeA) {
              closeA.addEventListener('click', function() {
                var modal = document.getElementById('citasModal');
                if (modal && window.closeModal) window.closeModal(modal);
              });
            }
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

            var canModify = (r.nombre_estado === 'Pendiente' || r.nombre_estado === 'Confirmada');
            var actionHtml = '';

            if (canModify) {
              actionHtml = '<div class="cita-card__actions">' +
                '<button type="button" class="btn--reschedule" ' +
                  'data-reschedule-cita="' + r.id_reserva + '" ' +
                  'data-servicio-id="' + (r.id_servicio || '') + '" ' +
                  'data-servicio-nombre="' + escHtml(r.nombre_servicio) + '" ' +
                  'data-esteticista-id="' + (r.id_esteticista || '0') + '" ' +
                  'data-esteticista-nombre="' + escHtml(r.nombre_esteticista || 'Aleatorio') + '" ' +
                  'data-fecha-str="' + fechaStr + '" ' +
                  'data-hora-str="' + horaStr + '">Reprogramar</button>' +
                '<button type="button" class="btn--danger-outline" data-cancel-cita="' + r.id_reserva + '">Cancelar</button>' +
              '</div>';
            } else if (r.nombre_estado === 'Cancelada') {
              actionHtml = '<div class="cita-card__actions"><span class="cita-card__note">Cita cancelada</span></div>';
            } else if (r.nombre_estado === 'Completada') {
              actionHtml = '<div class="cita-card__actions"><span class="cita-card__note">Tratamiento completado</span></div>';
            }

            return '<div class="cita-card">' +
              '<div class="cita-card__header">' +
                '<span class="cita-card__servicio">' + escHtml(r.nombre_servicio) + '</span>' +
                '<span class="status-badge status-badge--' + cls + '">' + escHtml(r.nombre_estado) + '</span>' +
              '</div>' +
              '<div class="cita-card__details">' +
                '<span>Fecha: <strong>' + fechaStr + '</strong></span>' +
                '<span>Hora: <strong>' + horaStr + '</strong></span>' +
                '<span>Especialista: <strong>' + escHtml(r.nombre_esteticista || 'Aleatorio') + '</strong></span>' +
                '<span>Duración: <strong>' + r.duracion_minutos + ' min</strong></span>' +
                '<span>Valor: <strong>$' + Number(r.precio).toLocaleString('es-CO') + '</strong></span>' +
              '</div>' +
              actionHtml +
            '</div>';
          }).join('');

          // Cancelar cita con confirmación
          container.querySelectorAll('[data-cancel-cita]').forEach(function(b) {
            b.addEventListener('click', function() {
              var id = parseInt(b.getAttribute('data-cancel-cita'));
              if (confirm('¿Estás seguro de que deseas cancelar esta cita? Esta acción liberará el espacio reservado.')) {
                cancelCita(id);
              }
            });
          });

          // Reprogramar cita
          container.querySelectorAll('[data-reschedule-cita]').forEach(function(b) {
            b.addEventListener('click', function() {
              openRescheduleModal({
                id: parseInt(b.getAttribute('data-reschedule-cita')),
                servicioId: parseInt(b.getAttribute('data-servicio-id') || '0'),
                servicioNombre: b.getAttribute('data-servicio-nombre') || '',
                esteticistaId: parseInt(b.getAttribute('data-esteticista-id') || '0'),
                esteticistaNombre: b.getAttribute('data-esteticista-nombre') || '',
                fechaStr: b.getAttribute('data-fecha-str') || '',
                horaStr: b.getAttribute('data-hora-str') || ''
              });
            });
          });
        })
        .catch(function() {
          container.innerHTML = '<p class="modal__empty">Error al cargar tus citas. Intenta de nuevo.</p>';
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
          showToast('Cita cancelada correctamente', 'info');
          loadCitas();
        } else {
          showToast(data.error || 'No se pudo cancelar la cita', 'error');
        }
      })
      .catch(function() { showToast('Error de conexión', 'error'); });
    }

    window.loadCitas = loadCitas;
  })();
  var currentRescheduleAppointment = null;

  function openRescheduleModal(apt) {
    currentRescheduleAppointment = apt;
    var modal = document.getElementById('rescheduleModal');
    if (!modal) return;

    var sNombre = document.getElementById('rescheduleServicioNombre');
    var eNombre = document.getElementById('rescheduleEspecialistaNombre');
    var actFH   = document.getElementById('rescheduleActualFechaHora');
    var idInput = document.getElementById('rescheduleReservaId');
    var sInput  = document.getElementById('rescheduleServicioId');
    var eInput  = document.getElementById('rescheduleEsteticistaId');
    var fechaIn = document.getElementById('rescheduleFecha');
    var horaSel = document.getElementById('rescheduleHora');
    var errEl   = document.getElementById('rescheduleError');

    if (sNombre) sNombre.textContent = apt.servicioNombre;
    if (eNombre) eNombre.textContent = apt.esteticistaNombre || 'Asignado';
    if (actFH) actFH.textContent = apt.fechaStr + ' a las ' + apt.horaStr;
    if (idInput) idInput.value = apt.id;
    if (sInput) sInput.value = apt.servicioId;
    if (eInput) eInput.value = apt.esteticistaId;
    if (errEl) errEl.style.display = 'none';

    var today = new Date().toISOString().split('T')[0];
    if (fechaIn) {
      fechaIn.setAttribute('min', today);
      fechaIn.value = today;
    }

    loadRescheduleSlots();

    if (window.openModal) {
      window.openModal('rescheduleModal');
    }
  }

  function loadRescheduleSlots() {
    var fechaIn = document.getElementById('rescheduleFecha');
    var horaSel = document.getElementById('rescheduleHora');
    var submitBtn = document.getElementById('confirmRescheduleBtn');
    if (!fechaIn || !horaSel || !currentRescheduleAppointment) return;

    var date = fechaIn.value;
    if (!date) return;

    var sId = currentRescheduleAppointment.servicioId || '';
    var eId = currentRescheduleAppointment.esteticistaId || 0;

    horaSel.innerHTML = '<option value="">Cargando horas disponibles...</option>';
    horaSel.disabled = true;
    if (submitBtn) submitBtn.disabled = true;

    var params = 'date=' + encodeURIComponent(date) +
                 '&servicio_id=' + encodeURIComponent(sId) +
                 '&esteticista_id=' + encodeURIComponent(eId);

    fetch('api/appointments/availability.php?' + params)
      .then(function(r) { return r.json(); })
      .then(function(data) {
        horaSel.disabled = false;
        if (!data.success || !data.slots || data.slots.length === 0) {
          horaSel.innerHTML = '<option value="">Sin horarios disponibles para esta fecha</option>';
          if (submitBtn) submitBtn.disabled = true;
          return;
        }

        horaSel.innerHTML = data.slots.map(function(s) {
          return '<option value="' + s + '">' + s + '</option>';
        }).join('');
        if (submitBtn) submitBtn.disabled = false;
      })
      .catch(function() {
        horaSel.disabled = false;
        horaSel.innerHTML = '<option value="">Error al cargar horarios</option>';
      });
  }

  (function initRescheduleEvents() {
    var fechaIn = document.getElementById('rescheduleFecha');
    if (fechaIn) {
      fechaIn.addEventListener('change', loadRescheduleSlots);
    }

    var form = document.getElementById('rescheduleForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
      e.preventDefault();

      var reservaId = document.getElementById('rescheduleReservaId').value;
      var date = document.getElementById('rescheduleFecha').value;
      var time = document.getElementById('rescheduleHora').value;
      var submitBtn = document.getElementById('confirmRescheduleBtn');
      var errEl = document.getElementById('rescheduleError');

      if (!date) {
        showError('rescheduleError', 'Por favor selecciona una fecha');
        return;
      }
      if (!time || time.indexOf('Sin horarios') !== -1 || time.indexOf('Cargando') !== -1) {
        showError('rescheduleError', 'Por favor selecciona un horario disponible');
        return;
      }

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Reprogramando...';
      }

      var body = {
        reserva_id: parseInt(reservaId),
        nueva_fecha: date,
        nueva_hora: time,
        date: date,
        time: time,
        id_esteticista: currentRescheduleAppointment ? currentRescheduleAppointment.esteticistaId : null,
        csrf_token: csrfToken
      };

      fetch('api/appointments/reschedule.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      })
      .then(function(res) {
        return res.json().then(function(data) {
          return { status: res.status, data: data };
        });
      })
      .then(function(result) {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Confirmar cambio';
        }
        var data = result.data;

        if (result.status === 200 && data.success) {
          showToast('¡Cita reprogramada exitosamente!', 'success');
          var modal = document.getElementById('rescheduleModal');
          if (modal && window.closeModal) window.closeModal(modal);
          if (window.loadCitas) window.loadCitas();
        } else {
          var msg = data.error || 'Error al reprogramar la cita.';
          showError('rescheduleError', msg);
          showToast(msg, 'error');
          loadRescheduleSlots();
        }
      })
      .catch(function() {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Confirmar cambio';
        }
        showError('rescheduleError', 'Error de conexión. Intenta de nuevo.');
        showToast('Error de conexión. Intenta de nuevo.', 'error');
      });
    });
  })();
  function openCompleteAccountModal(info) {
    var modal = document.getElementById('completeAccountModal');
    if (!modal) return;

    var nIn = document.getElementById('completeNombre');
    var cIn = document.getElementById('completeCorreo');
    var tIn = document.getElementById('completeTelefono');
    var pIn = document.getElementById('completePassword');
    var pCIn = document.getElementById('completePasswordConfirm');
    var err = document.getElementById('completeAccountError');

    if (info) {
      if (nIn && info.nombre) nIn.value = info.nombre;
      if (cIn && info.correo) cIn.value = info.correo;
      if (tIn && info.telefono) tIn.value = info.telefono;
    }
    if (pIn) pIn.value = '';
    if (pCIn) pCIn.value = '';
    if (err) err.style.display = 'none';

    if (window.openModal) {
      window.openModal('completeAccountModal');
    }
  }
  (function() {
    var params = new URLSearchParams(window.location.search);
    if (params.get('completar') !== '1') return;
    if (!document.getElementById('completeAccountModal')) return;

    // Si ya hay sesion no tiene sentido completar la cuenta
    var loggedBox = document.getElementById('userMenuLogged');
    if (loggedBox && loggedBox.style.display !== 'none') return;

    openCompleteAccountModal({ correo: params.get('email') || '' });

    // Limpiar la URL para que no se reabra al recargar
    if (window.history && window.history.replaceState) {
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  })();

  (function initCompleteAccountEvents() {
    var form = document.getElementById('completeAccountForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
      e.preventDefault();

      var nombre = document.getElementById('completeNombre').value.trim();
      var correo = document.getElementById('completeCorreo').value.trim();
      var telefono = document.getElementById('completeTelefono').value.trim();
      var password = document.getElementById('completePassword').value;
      var confirm = document.getElementById('completePasswordConfirm').value;
      var btn = document.getElementById('completeAccountBtn');

      if (!nombre) {
        showError('completeAccountError', 'Por favor ingresa tu nombre completo');
        return;
      }
      if (!correo || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
        showError('completeAccountError', 'Por favor ingresa un correo electrónico válido');
        return;
      }
      if (!telefono || telefono.replace(/[\s+-]/g, '').length < 7) {
        showError('completeAccountError', 'Por favor ingresa un número de teléfono válido (mínimo 7 dígitos)');
        return;
      }
      if (password.length < 8) {
        showError('completeAccountError', 'La contraseña debe tener al menos 8 caracteres');
        showToast('La contraseña debe tener al menos 8 caracteres', 'error');
        return;
      }
      if (password !== confirm) {
        showError('completeAccountError', 'Las contraseñas no coinciden');
        showToast('Las contraseñas no coinciden', 'error');
        return;
      }

      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Activando cuenta...';
      }

      var payload = {
        name: nombre,
        nombre: nombre,
        email: correo,
        correo: correo,
        phone: telefono,
        telefono: telefono,
        password: password,
        password_confirm: confirm,
        csrf_token: csrfToken
      };

      // Intentar primero con complete-profile.php, con fallback a register.php
      fetch('api/auth/complete-profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(function(res) {
        if (res.status === 404) {
          return fetch('api/auth/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              name: nombre,
              email: correo,
              password: password,
              password_confirm: confirm,
              csrf_token: csrfToken
            })
          }).then(function(r) { return r.json(); });
        }
        return res.json();
      })
      .then(function(data) {
        if (btn) {
          btn.disabled = false;
          btn.textContent = 'Activar mi cuenta';
        }

        if (data.success) {
          if (data.csrf_token) csrfToken = data.csrf_token;
          updateAllCsrfTokens(csrfToken);
          setLoggedIn(data.user);
          var modal = document.getElementById('completeAccountModal');
          if (modal && window.closeModal) window.closeModal(modal);
          showToast('¡Tu cuenta ha sido activada con éxito!', 'success');

          var msgEl = document.getElementById('bookingMessage');
          if (msgEl) {
            msgEl.innerHTML = '<span style="color:#27ae60; font-weight:600;">¡Cuenta activada con éxito!</span> Ya puedes consultar y reprogramar tus citas en cualquier momento.';
          }
        } else {
          showError('completeAccountError', data.error || 'Error al activar tu cuenta');
          showToast(data.error || 'Error al activar tu cuenta', 'error');
        }
      })
      .catch(function() {
        if (btn) {
          btn.disabled = false;
          btn.textContent = 'Activar mi cuenta';
        }
        showError('completeAccountError', 'Error de conexión. Intenta de nuevo.');
        showToast('Error de conexión. Intenta de nuevo.', 'error');
      });
    });
  })();
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
            updateAvatar(data.user);
          }
        });
    });

    form.addEventListener('submit', function(e) {
      e.preventDefault();

      var currentPassword = document.getElementById('perfilPassActual').value;
      var newPassword = document.getElementById('perfilPassNueva').value;
      if (newPassword !== '') {
        if (newPassword.length < 8) {
          showError('perfilError', 'La nueva contraseña debe tener al menos 8 caracteres');
          showToast('La nueva contraseña debe tener al menos 8 caracteres', 'error');
          return;
        }
        if (!currentPassword) {
          showError('perfilError', 'Ingresa tu contraseña actual para confirmar el cambio');
          showToast('Ingresa tu contraseña actual para confirmar el cambio', 'warning');
          return;
        }
      }

      var body = {
        name: document.getElementById('perfilNombre').value.trim(),
        phone: document.getElementById('perfilTelefono').value.trim(),
        current_password: currentPassword,
        new_password: newPassword,
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
          showToast('Perfil actualizado con éxito', 'success');
          var modal = document.getElementById('perfilModal');
          if (modal) window.closeModal(modal);
          var nameEl = document.querySelector('.user-menu__name');
          if (nameEl) nameEl.textContent = body.name;
          updateAvatar({ name: body.name });
          autofillBookingForm({ name: body.name, phone: body.phone });
        } else {
          showError('perfilError', data.error || 'Error al guardar');
          showToast(data.error || 'Error al guardar', 'error');
        }
      })
      .catch(function() {
        showToast('Error de conexión', 'error');
      });
    });
  })();
  (function initSessionAutofill() {
    var logged = document.getElementById('userMenuLogged');
    if (logged && logged.style.display !== 'none') {
      fetch('api/auth/profile.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.success && data.user) {
            autofillBookingForm(data.user);
            updateAvatar(data.user);
          }
        })
        .catch(function() {});
    }
  })();
  (function() {
    // El listado solo existe en el area de cliente, pero el corazon de las
    // tarjetas esta en el sitio publico: toggleFav se define siempre.
    var btn = document.getElementById('openFavoritosBtn');
    if (btn) btn.addEventListener('click', loadFavoritos);

    function loadFavoritos() {
      var container = document.getElementById('favoritosList');
      if (!container) return;
      container.innerHTML = '<p class="modal__empty">Cargando...</p>';

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
