(function() {
  'use strict';

  // Elementos base
  var csrfToken       = document.getElementById('dashCsrf') ? document.getElementById('dashCsrf').value : '';
  var currentUserRole = document.getElementById('currentUserRole') ? parseInt(document.getElementById('currentUserRole').value, 10) : 0;
  var currentUserId   = document.getElementById('currentUserId') ? parseInt(document.getElementById('currentUserId').value, 10) : 0;
  var tbody           = document.getElementById('reservasBody');

  // Estados de reserva
  var estadoClasses = {
    'Pendiente': 'pendiente', 'Confirmada': 'confirmada', 'Completada': 'completada',
    'Cancelada': 'cancelada', 'Reasignada': 'reasignada', 'No_Show': 'no_show', 'No Show': 'no_show'
  };

  var estadoNames = ['', 'Pendiente', 'Confirmada', 'Completada', 'Cancelada', 'Reasignada', 'No Show'];

  // Pila Undo / Redo (Tarea 3)
  var undoStack = [];
  var redoStack = [];

  // ============================================================
  // UTILIDADES
  // ============================================================
  function escHtml(str) {
    if (str === null || str === undefined) return '';
    var div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
  }

  function formatMoney(amount) {
    return '$' + Number(amount || 0).toLocaleString('es-CO');
  }

  function showToast(msg, type) {
    var container = document.getElementById('toastContainer');
    if (!container) return;

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

  // ============================================================
  // NAVEGACIÓN Y BARRA LATERAL
  // La logica vive en js/panel.js, compartida con el area de cliente.
  // ============================================================
  var showView = window.PanelUI.showView;

  // TAREA 16: MODO OSCURO (DARK MODE)
  // ============================================================
  var themeToggleBtn  = document.getElementById('themeToggleBtn');
  var themeToggleText = document.getElementById('themeToggleText');

  function applyTheme(theme) {
    if (theme === 'dark') {
      document.documentElement.classList.add('dark-mode');
      document.body.classList.add('dark-mode');
      if (themeToggleText) themeToggleText.textContent = 'Modo Claro';
      if (themeToggleBtn) {
        themeToggleBtn.setAttribute('title', 'Cambiar a Modo Claro');
        themeToggleBtn.setAttribute('aria-label', 'Cambiar a Modo Claro');
      }
    } else {
      document.documentElement.classList.remove('dark-mode');
      document.body.classList.remove('dark-mode');
      if (themeToggleText) themeToggleText.textContent = 'Modo Oscuro';
      if (themeToggleBtn) {
        themeToggleBtn.setAttribute('title', 'Cambiar a Modo Oscuro');
        themeToggleBtn.setAttribute('aria-label', 'Cambiar a Modo Oscuro');
      }
    }
  }

  // Inicializar tema desde localStorage
  var savedTheme = localStorage.getItem('admin_theme') || 'light';
  applyTheme(savedTheme);

  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', function() {
      var isDark = document.documentElement.classList.contains('dark-mode');
      var newTheme = isDark ? 'light' : 'dark';
      localStorage.setItem('admin_theme', newTheme);
      applyTheme(newTheme);
      showToast(newTheme === 'dark' ? 'Modo Oscuro activado' : 'Modo Claro activado', 'info');
    });
  }

  // ============================================================
  // GESTIÓN DE MODALES
  // ============================================================
  function openModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('modal--open');
      var firstInput = modal.querySelector('input:not([type=hidden]), select, textarea');
      if (firstInput) firstInput.focus();
    }
  }

  function closeModal(modal) {
    if (typeof modal === 'string') {
      modal = document.getElementById(modal);
    }
    if (modal) {
      modal.classList.remove('modal--open');
    }
  }

  // Delegar cierre de modales con botones data-close-modal o clic en backdrop
  document.addEventListener('click', function(e) {
    if (e.target.matches('[data-close-modal]') || e.target.closest('[data-close-modal]')) {
      var modal = e.target.closest('.modal');
      if (modal) closeModal(modal);
    } else if (e.target.classList.contains('modal')) {
      closeModal(e.target);
    }
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      var openM = document.querySelector('.modal.modal--open');
      if (openM) closeModal(openM);
    }
  });

  // ============================================================
  // CARGA Y FILTRADO DE RESERVAS (Tareas 18, 8 y 23)
  // ============================================================
  function updateCounters(stats) {
    if (!stats) return;
    var total = 0;
    for (var k in stats) {
      if (stats.hasOwnProperty(k)) total += parseInt(stats[k], 10);
    }
    var statTotal = document.getElementById('statTotal');
    var statPend = document.getElementById('statPendiente');
    var statConf = document.getElementById('statConfirmada');
    var statComp = document.getElementById('statCompletada');

    if (statTotal) statTotal.textContent = total;
    if (statPend)  statPend.textContent = stats['Pendiente'] || 0;
    if (statConf)  statConf.textContent = stats['Confirmada'] || 0;
    if (statComp)  statComp.textContent = stats['Completada'] || 0;
  }

  function loadReservas() {
    var params = new URLSearchParams();
    var fechaEl = document.getElementById('filterFecha');
    var estadoEl = document.getElementById('filterEstado');
    var servicioEl = document.getElementById('filterServicio');
    var esteticistaEl = document.getElementById('filterEsteticista');

    var fecha       = fechaEl ? fechaEl.value : '';
    var estado      = estadoEl ? estadoEl.value : '';
    var servicio    = servicioEl ? servicioEl.value : '';
    var esteticista = esteticistaEl ? esteticistaEl.value : '';

    if (fecha)       params.set('fecha', fecha);
    if (estado)      params.set('estado', estado);
    if (servicio)    params.set('servicio', servicio);
    if (esteticista) params.set('esteticista', esteticista);

    tbody.innerHTML = '<tr><td colspan="9" class="dashboard__empty">Cargando reservas...</td></tr>';

    var url = 'api/appointments/all.php' + (params.toString() ? '?' + params.toString() : '');

    fetch(url)
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success) {
          tbody.innerHTML = '<tr><td colspan="9" class="dashboard__empty">' + escHtml(data.error || 'Error al cargar reservas') + '</td></tr>';
          return;
        }

        if (data.stats) {
          updateCounters(data.stats);
        }

        var hint = document.getElementById('reservasCount');
        var total = (data.reservas || []).length;
        if (hint) {
          hint.textContent = total === 0
            ? 'Sin resultados para los filtros actuales'
            : total + (total === 1 ? ' cita encontrada' : ' citas encontradas');
        }

        if (!data.reservas || data.reservas.length === 0) {
          tbody.innerHTML = '<tr><td colspan="9" class="dashboard__empty">No se encontraron reservas con los filtros seleccionados</td></tr>';
          return;
        }

        tbody.innerHTML = data.reservas.map(function(r) {
          var fechaInicio = new Date(r.fecha_hora_inicio.replace(/-/g, '/'));
          var fechaStr = isNaN(fechaInicio.getTime()) ? r.fecha_hora_inicio :
            fechaInicio.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
          var horaStr = isNaN(fechaInicio.getTime()) ? '' :
            fechaInicio.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', hour12: true });

          var cls = estadoClasses[r.nombre_estado] || 'pendiente';
          var estadoId = parseInt(r.id_estado, 10);

          var selectHtml = '<select class="dashboard__status-select" data-reserva="' + r.id_reserva + '" data-previous="' + estadoId + '">';
          for (var i = 1; i <= 6; i++) {
            selectHtml += '<option value="' + i + '"' + (estadoId === i ? ' selected' : '') + '>' + estadoNames[i] + '</option>';
          }
          selectHtml += '</select>';

          var telInfo = r.telefono_cliente ? '<br><small class="cell-muted">📞 ' + escHtml(r.telefono_cliente) + '</small>' : '';

          // Tarea 23: Botón para reprogramar cita en cada fila activa
          var canReschedule = (estadoId === 1 || estadoId === 2 || estadoId === 5);
          var reprogBtn = canReschedule ?
            '<button type="button" class="btn btn--outline btn--xs btn-reprogramar" ' +
              'data-id="' + r.id_reserva + '" ' +
              'data-servicio-id="' + r.id_servicio + '" ' +
              'data-servicio-nombre="' + escHtml(r.nombre_servicio) + '" ' +
              'data-esteticista-id="' + r.id_esteticista + '" ' +
              'data-esteticista-nombre="' + escHtml(r.nombre_esteticista) + '" ' +
              'data-cliente="' + escHtml(r.nombre_cliente) + '" ' +
              'data-fecha="' + r.fecha_hora_inicio.substring(0, 10) + '" ' +
              'title="Reprogramar fecha u hora">' +
              '🗓 Reprogramar' +
            '</button>' :
            '<span class="cell-muted">—</span>';

          return '<tr data-reserva-row="' + r.id_reserva + '">' +
            '<td data-label="#"><strong>#' + r.id_reserva + '</strong></td>' +
            '<td data-label="Cliente">' + escHtml(r.nombre_cliente) + '<br><small class="cell-muted">' + escHtml(r.correo_cliente) + '</small>' + telInfo + '</td>' +
            '<td data-label="Servicio">' + escHtml(r.nombre_servicio) + '<br><small class="cell-muted">' + formatMoney(r.precio) + '</small></td>' +
            '<td data-label="Especialista">' + escHtml(r.nombre_esteticista) + '</td>' +
            '<td data-label="Fecha">' + fechaStr + '</td>' +
            '<td data-label="Hora">' + horaStr + '</td>' +
            '<td data-label="Estado"><span class="status-badge status-badge--' + cls + '">' + escHtml(r.nombre_estado) + '</span></td>' +
            '<td data-label="Cambiar estado">' + selectHtml + '</td>' +
            '<td data-label="Acciones">' + reprogBtn + '</td>' +
          '</tr>';
        }).join('');

        // Listeners para cambio de estado en el select
        tbody.querySelectorAll('.dashboard__status-select').forEach(function(sel) {
          sel.addEventListener('focus', function() {
            this.dataset.previous = this.value;
          });

          sel.addEventListener('change', function() {
            var rId = parseInt(this.dataset.reserva, 10);
            var oldVal = parseInt(this.dataset.previous, 10);
            var newVal = parseInt(this.value, 10);

            if (oldVal !== newVal) {
              updateStatusWithUndo(rId, oldVal, newVal);
            }
          });
        });

        // Listeners para botones de Reprogramar (Tarea 23)
        tbody.querySelectorAll('.btn-reprogramar').forEach(function(btn) {
          btn.addEventListener('click', function() {
            openReprogramarModal({
              id: this.dataset.id,
              servicioId: this.dataset.servicioId,
              servicioNombre: this.dataset.servicioNombre,
              esteticistaId: this.dataset.esteticistaId,
              esteticistaNombre: this.dataset.esteticistaNombre,
              cliente: this.dataset.cliente,
              fecha: this.dataset.fecha
            });
          });
        });

      })
      .catch(function(err) {
        tbody.innerHTML = '<tr><td colspan="9" class="dashboard__empty">Error de conexión al cargar reservas</td></tr>';
      });
  }

  // ============================================================
  // PILA DESHACER / REHACER (UNDO / REDO) - TAREA 3
  // ============================================================
  var btnUndo = document.getElementById('btnUndo');
  var btnRedo = document.getElementById('btnRedo');

  function updateUndoRedoButtons() {
    if (btnUndo) btnUndo.disabled = (undoStack.length === 0);
    if (btnRedo) btnRedo.disabled = (redoStack.length === 0);
  }

  function updateStatus(reservaId, estadoId, callback) {
    fetch('api/appointments/update-status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ reserva_id: reservaId, estado_id: estadoId, csrf_token: csrfToken })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        loadReservas();
        if (callback) callback(null, data);
      } else {
        showToast(data.error || 'Error al actualizar', 'error');
        if (callback) callback(new Error(data.error));
      }
    })
    .catch(function(err) {
      showToast('Error de conexión', 'error');
      if (callback) callback(err);
    });
  }

  function updateStatusWithUndo(reservaId, oldEstado, newEstado) {
    updateStatus(reservaId, newEstado, function(err, data) {
      if (!err) {
        undoStack.push({ reservaId: reservaId, oldEstado: oldEstado, newEstado: newEstado });
        redoStack = []; // Se vacía el historial de rehacer al haber una nueva acción
        updateUndoRedoButtons();
        showToast('Estado cambiado a ' + estadoNames[newEstado], 'success');
      }
    });
  }

  if (btnUndo) {
    btnUndo.addEventListener('click', function() {
      if (undoStack.length === 0) return;
      var action = undoStack.pop();
      updateStatus(action.reservaId, action.oldEstado, function(err) {
        if (!err) {
          redoStack.push(action);
          updateUndoRedoButtons();
          showToast('Deshecho: Reserva #' + action.reservaId + ' revertida a ' + estadoNames[action.oldEstado], 'info');
        } else {
          undoStack.push(action); // Recuperar si falló
        }
      });
    });
  }

  if (btnRedo) {
    btnRedo.addEventListener('click', function() {
      if (redoStack.length === 0) return;
      var action = redoStack.pop();
      updateStatus(action.reservaId, action.newEstado, function(err) {
        if (!err) {
          undoStack.push(action);
          updateUndoRedoButtons();
          showToast('Rehecho: Reserva #' + action.reservaId + ' cambiada a ' + estadoNames[action.newEstado], 'success');
        } else {
          redoStack.push(action); // Recuperar si falló
        }
      });
    });
  }

  // ============================================================
  // FILTROS DE LA TABLA
  // ============================================================
  var filterFecha = document.getElementById('filterFecha');
  var filterEstado = document.getElementById('filterEstado');
  var filterServicio = document.getElementById('filterServicio');
  var filterEsteticista = document.getElementById('filterEsteticista');
  var btnResetFilters = document.getElementById('btnResetFilters');

  if (filterFecha)       filterFecha.addEventListener('change', loadReservas);
  if (filterEstado)      filterEstado.addEventListener('change', loadReservas);
  if (filterServicio)    filterServicio.addEventListener('change', loadReservas);
  if (filterEsteticista && filterEsteticista.tagName === 'SELECT') {
    filterEsteticista.addEventListener('change', loadReservas);
  }

  if (btnResetFilters) {
    btnResetFilters.addEventListener('click', function() {
      if (filterFecha) filterFecha.value = '';
      if (filterEstado) filterEstado.value = '';
      if (filterServicio) filterServicio.value = '';
      if (filterEsteticista && filterEsteticista.tagName === 'SELECT') {
        filterEsteticista.value = '';
      }
      loadReservas();
      showToast('Filtros restablecidos', 'info');
    });
  }

  // ============================================================
  // MODAL: REPROGRAMAR CITA (TAREA 23)
  // ============================================================
  var modalReprog       = document.getElementById('modalReprogramar');
  var formReprog        = document.getElementById('formReprogramar');
  var reprogReservaId   = document.getElementById('reprogReservaId');
  var reprogServicioId  = document.getElementById('reprogServicioId');
  var reprogEspecialista= document.getElementById('reprogEspecialista');
  var reprogFecha       = document.getElementById('reprogFecha');
  var reprogHora        = document.getElementById('reprogHora');
  var reprogSummaryBox  = document.getElementById('reprogSummaryBox');
  var reprogError       = document.getElementById('reprogError');
  var reprogSlotsHelp   = document.getElementById('reprogSlotsHelp');

  function openReprogramarModal(data) {
    if (!modalReprog) return;
    reprogReservaId.value  = data.id;
    reprogServicioId.value = data.servicioId;
    
    if (reprogEspecialista) {
      reprogEspecialista.value = data.esteticistaId;
    }

    if (reprogFecha) {
      reprogFecha.value = data.fecha || new Date().toISOString().substring(0, 10);
    }

    if (reprogSummaryBox) {
      reprogSummaryBox.innerHTML = '<strong>Cita #' + data.id + '</strong> — Cliente: <em>' + data.cliente + '</em><br>' +
        'Tratamiento: <strong>' + data.servicioNombre + '</strong><br>' +
        'Especialista actual: <strong>' + data.esteticistaNombre + '</strong>';
    }

    if (reprogError) {
      reprogError.style.display = 'none';
      reprogError.textContent = '';
    }

    openModal('modalReprogramar');
    fetchAvailableSlotsForReprog();
  }

  function fetchAvailableSlotsForReprog() {
    if (!reprogServicioId || !reprogFecha || !reprogHora) return;
    var servId = reprogServicioId.value;
    var fecha  = reprogFecha.value;
    var estId  = reprogEspecialista ? reprogEspecialista.value : '';

    if (!servId || !fecha) return;

    reprogHora.innerHTML = '<option value="">Consultando horarios...</option>';
    if (reprogSlotsHelp) reprogSlotsHelp.textContent = 'Buscando horarios disponibles sin conflictos...';

    var url = 'api/appointments/availability.php?servicio_id=' + encodeURIComponent(servId) +
              '&date=' + encodeURIComponent(fecha) +
              (estId ? '&esteticista_id=' + encodeURIComponent(estId) : '');

    fetch(url)
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success || !data.slots || data.slots.length === 0) {
          reprogHora.innerHTML = '<option value="">No hay horarios disponibles en esta fecha</option>';
          if (reprogSlotsHelp) reprogSlotsHelp.textContent = 'Prueba con otra fecha u otro especialista.';
          return;
        }

        reprogHora.innerHTML = '<option value="">Selecciona un horario...</option>' +
          data.slots.map(function(slot) {
            return '<option value="' + escHtml(slot) + '">' + escHtml(slot) + '</option>';
          }).join('');

        if (reprogSlotsHelp) reprogSlotsHelp.textContent = data.slots.length + ' turnos disponibles.';
      })
      .catch(function() {
        reprogHora.innerHTML = '<option value="">Error al cargar horarios</option>';
      });
  }

  if (reprogFecha) {
    reprogFecha.addEventListener('change', fetchAvailableSlotsForReprog);
  }
  if (reprogEspecialista) {
    reprogEspecialista.addEventListener('change', fetchAvailableSlotsForReprog);
  }

  if (formReprog) {
    formReprog.addEventListener('submit', function(e) {
      e.preventDefault();
      var rId   = parseInt(reprogReservaId.value, 10);
      var fecha = reprogFecha.value;
      var hora  = reprogHora.value;
      var estId = reprogEspecialista ? parseInt(reprogEspecialista.value, 10) : null;

      if (!rId || !fecha || !hora) {
        reprogError.textContent = 'Selecciona una fecha y un horario disponible';
        reprogError.style.display = 'block';
        return;
      }

      var btnSub = document.getElementById('btnSubmitReprog');
      if (btnSub) { btnSub.disabled = true; btnSub.textContent = 'Reprogramando...'; }

      fetch('api/appointments/reschedule.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          reserva_id: rId,
          date: fecha,
          time: hora,
          esteticista_id: estId,
          csrf_token: csrfToken
        })
      })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (btnSub) { btnSub.disabled = false; btnSub.textContent = 'Confirmar Reprogramación'; }
        if (res.success) {
          showToast(res.message || 'Cita reprogramada con éxito', 'success');
          closeModal('modalReprogramar');
          loadReservas();
        } else {
          reprogError.textContent = res.error || 'Error al reprogramar la cita';
          reprogError.style.display = 'block';
        }
      })
      .catch(function() {
        if (btnSub) { btnSub.disabled = false; btnSub.textContent = 'Confirmar Reprogramación'; }
        reprogError.textContent = 'Error de conexión con el servidor';
        reprogError.style.display = 'block';
      });
    });
  }

  // ============================================================
  // MODAL: NUEVA CITA TELEFÓNICA (RECEPCIÓN)
  // ============================================================
  var btnOpenNuevaCita = document.getElementById('btnOpenNuevaCita');
  var formNuevaCita    = document.getElementById('formNuevaCita');
  var ncServicio       = document.getElementById('ncServicio');
  var ncEspecialista   = document.getElementById('ncEspecialista');
  var ncFecha          = document.getElementById('ncFecha');
  var ncHora           = document.getElementById('ncHora');
  var ncError          = document.getElementById('ncError');

  if (btnOpenNuevaCita) {
    btnOpenNuevaCita.addEventListener('click', function() {
      if (formNuevaCita) formNuevaCita.reset();
      if (ncError) ncError.style.display = 'none';
      if (ncFecha) ncFecha.value = new Date().toISOString().substring(0, 10);
      fetchAvailableSlotsForNuevaCita();
    });
  }

  function fetchAvailableSlotsForNuevaCita() {
    if (!ncServicio || !ncFecha || !ncHora) return;
    var servId = ncServicio.value;
    var fecha  = ncFecha.value;
    var estId  = ncEspecialista ? ncEspecialista.value : '';

    if (!servId || !fecha) {
      ncHora.innerHTML = '<option value="">Selecciona servicio y fecha primero</option>';
      return;
    }

    ncHora.innerHTML = '<option value="">Consultando disponibilidad...</option>';

    var url = 'api/appointments/availability.php?servicio_id=' + encodeURIComponent(servId) +
              '&date=' + encodeURIComponent(fecha) +
              (estId ? '&esteticista_id=' + encodeURIComponent(estId) : '');

    fetch(url)
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success || !data.slots || data.slots.length === 0) {
          ncHora.innerHTML = '<option value="">Sin turnos disponibles en esta fecha</option>';
          return;
        }
        ncHora.innerHTML = '<option value="">Selecciona horario...</option>' +
          data.slots.map(function(s) {
            return '<option value="' + escHtml(s) + '">' + escHtml(s) + '</option>';
          }).join('');
      })
      .catch(function() {
        ncHora.innerHTML = '<option value="">Error al cargar turnos</option>';
      });
  }

  if (ncServicio)     ncServicio.addEventListener('change', fetchAvailableSlotsForNuevaCita);
  if (ncEspecialista) ncEspecialista.addEventListener('change', fetchAvailableSlotsForNuevaCita);
  if (ncFecha)        ncFecha.addEventListener('change', fetchAvailableSlotsForNuevaCita);

  if (formNuevaCita) {
    formNuevaCita.addEventListener('submit', function(e) {
      e.preventDefault();
      var nombre = document.getElementById('ncNombre').value.trim();
      var correo = document.getElementById('ncCorreo').value.trim();
      var tel    = document.getElementById('ncTelefono').value.trim();
      var serv   = parseInt(ncServicio.value, 10);
      var est    = ncEspecialista ? parseInt(ncEspecialista.value, 10) : 0;
      var f      = ncFecha.value;
      var h      = ncHora.value;

      if (!nombre || !correo || !serv || !f || !h) {
        ncError.textContent = 'Completa todos los campos obligatorios';
        ncError.style.display = 'block';
        return;
      }

      var btnSub = document.getElementById('btnSubmitNuevaCita');
      if (btnSub) { btnSub.disabled = true; btnSub.textContent = 'Guardando cita...'; }

      fetch('api/appointments/create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          nombre: nombre,
          correo: correo,
          telefono: tel,
          servicio_id: serv,
          esteticista_id: est || 0,
          date: f,
          time: h,
          csrf_token: csrfToken
        })
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (btnSub) { btnSub.disabled = false; btnSub.textContent = 'Guardar y Confirmar Cita'; }
        if (data.success) {
          showToast('Cita creada exitosamente #' + data.reserva.id, 'success');
          showView('viewDashboard');
          loadReservas();
        } else {
          ncError.textContent = data.error || 'Error al agendar cita';
          ncError.style.display = 'block';
        }
      })
      .catch(function() {
        if (btnSub) { btnSub.disabled = false; btnSub.textContent = 'Guardar y Confirmar Cita'; }
        ncError.textContent = 'Error de conexión con el servidor';
        ncError.style.display = 'block';
      });
    });
  }

  // ============================================================
  // MODAL: BLOQUEO DE AGENDA / DESCANSOS
  // ============================================================
  var btnOpenBloqueo     = document.getElementById('btnOpenBloqueo');
  var formBloqueo        = document.getElementById('formBloqueo');
  var tablaBloqueosBody  = document.getElementById('tablaBloqueosBody');
  var bloqError          = document.getElementById('bloqError');

  function loadBloqueos() {
    if (!tablaBloqueosBody) return;
    tablaBloqueosBody.innerHTML = '<tr><td colspan="5" class="dashboard__empty">Cargando bloqueos...</td></tr>';

    fetch('api/appointments/blocks.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success || !data.bloqueos || data.bloqueos.length === 0) {
          tablaBloqueosBody.innerHTML = '<tr><td colspan="5" class="dashboard__empty">No hay bloqueos activos</td></tr>';
          return;
        }

        tablaBloqueosBody.innerHTML = data.bloqueos.map(function(b) {
          var inicio = new Date(b.fecha_hora_inicio.replace(/-/g, '/'));
          var fin    = new Date(b.fecha_hora_fin.replace(/-/g, '/'));
          var iniStr = isNaN(inicio.getTime()) ? b.fecha_hora_inicio : inicio.toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' });
          var finStr = isNaN(fin.getTime()) ? b.fecha_hora_fin : fin.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', hour12: true });

          return '<tr>' +
            '<td><strong>' + escHtml(b.nombre_esteticista) + '</strong></td>' +
            '<td>' + iniStr + '</td>' +
            '<td>' + finStr + '</td>' +
            '<td><span class="status-badge" style="background:#eee;color:#333;">' + escHtml(b.motivo) + '</span></td>' +
            '<td><button type="button" class="btn btn--outline btn--xs btn-delete-block" data-id="' + b.id_bloqueo + '" style="color:#e74c3c;">✕ Quitar</button></td>' +
          '</tr>';
        }).join('');

        tablaBloqueosBody.querySelectorAll('.btn-delete-block').forEach(function(btn) {
          btn.addEventListener('click', function() {
            var bId = parseInt(this.dataset.id, 10);
            if (confirm('¿Deseas desbloquear este horario?')) {
              deleteBlock(bId);
            }
          });
        });
      })
      .catch(function() {
        tablaBloqueosBody.innerHTML = '<tr><td colspan="5" class="dashboard__empty">Error al cargar bloqueos</td></tr>';
      });
  }

  function deleteBlock(bId) {
    fetch('api/appointments/blocks.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', id_bloqueo: bId, csrf_token: csrfToken })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (res.success) {
        showToast('Bloqueo eliminado', 'success');
        loadBloqueos();
      } else {
        showToast(res.error || 'Error al eliminar', 'error');
      }
    });
  }

  if (btnOpenBloqueo) {
    btnOpenBloqueo.addEventListener('click', function() {
      if (bloqError) bloqError.style.display = 'none';
      loadBloqueos();
    });
  }

  if (formBloqueo) {
    formBloqueo.addEventListener('submit', function(e) {
      e.preventDefault();
      var estId   = parseInt(document.getElementById('bloqEspecialista').value, 10);
      var fecha   = document.getElementById('bloqFecha').value;
      var hInicio = document.getElementById('bloqHoraInicio').value;
      var hFin    = document.getElementById('bloqHoraFin').value;
      var motivo  = document.getElementById('bloqMotivo').value;

      if (!estId || !fecha || !hInicio || !hFin) {
        bloqError.textContent = 'Todos los campos son obligatorios';
        bloqError.style.display = 'block';
        return;
      }

      fetch('api/appointments/blocks.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'create',
          id_esteticista: estId,
          fecha: fecha,
          hora_inicio: hInicio,
          hora_fin: hFin,
          motivo: motivo,
          csrf_token: csrfToken
        })
      })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (res.success) {
          showToast(res.message || 'Bloqueo registrado', 'success');
          if (bloqError) bloqError.style.display = 'none';
          loadBloqueos();
        } else {
          bloqError.textContent = res.error || 'Error al registrar bloqueo';
          bloqError.style.display = 'block';
        }
      })
      .catch(function() {
        bloqError.textContent = 'Error de conexión';
        bloqError.style.display = 'block';
      });
    });
  }

  // ============================================================
  // MODAL: CONTINGENCIA DE ESPECIALISTA
  // ============================================================
  var btnOpenContingencia        = document.getElementById('btnOpenContingencia');
  var btnConsultarContingencia   = document.getElementById('btnConsultarContingencia');
  var contResultados             = document.getElementById('contResultados');
  var contCitasBody              = document.getElementById('contCitasBody');
  var contTotalAfectadas         = document.getElementById('contTotalAfectadas');
  var contSelectAll              = document.getElementById('contSelectAll');
  var btnReasignarLote           = document.getElementById('btnReasignarLote');
  var btnCancelarLote            = document.getElementById('btnCancelarLote');
  var btnBloquearDiaContingencia = document.getElementById('btnBloquearDiaContingencia');

  if (btnOpenContingencia) {
    btnOpenContingencia.addEventListener('click', function() {
      resetContingencyForm();
      loadContingencyHistory();
    });
  }

  // Deja la pantalla como al entrar: sin resultados ni seleccion previa.
  function resetContingencyForm() {
    if (contResultados) contResultados.style.display = 'none';
    if (contCitasBody)  contCitasBody.innerHTML = '';
    if (contTotalAfectadas) contTotalAfectadas.textContent = '0';
    if (contSelectAll) contSelectAll.checked = false;

    var destino = document.getElementById('contNuevoEsteticista');
    if (destino) destino.selectedIndex = 0;

    var fecha = document.getElementById('contFecha');
    if (fecha) fecha.value = '';

    var especialista = document.getElementById('contEsteticista');
    if (especialista) especialista.selectedIndex = 0;
  }

  // Texto legible para cada tipo de accion registrada en auditoria.
  var ETIQUETAS_CONTINGENCIA = {
    BLOCK_DAY:            { texto: 'Día bloqueado',     clase: 'reasignada' },
    REASSIGN_APPOINTMENT: { texto: 'Cita reasignada',   clase: 'confirmada' },
    CANCEL_APPOINTMENT:   { texto: 'Cita cancelada',    clase: 'cancelada'  },
    APPLY_CONTINGENCY:    { texto: 'Plan aplicado',     clase: 'pendiente'  }
  };

  function loadContingencyHistory() {
    var lista = document.getElementById('contHistorial');
    if (!lista) return;

    fetch('api/admin/contingency.php?historial=1')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success || !data.historial || data.historial.length === 0) {
          lista.innerHTML = '<li class="timeline__vacio">Todavía no se ha realizado ninguna acción de contingencia</li>';
          return;
        }

        lista.innerHTML = data.historial.map(function(item) {
          var etiqueta = ETIQUETAS_CONTINGENCIA[item.accion] || { texto: item.accion, clase: 'pendiente' };
          var cuando = item.fecha_hora ? item.fecha_hora.substring(0, 16).replace(' ', ' · ') : '';

          return '<li class="timeline__item">' +
            '<span class="status-badge status-badge--' + etiqueta.clase + '">' + escHtml(etiqueta.texto) + '</span>' +
            '<div class="timeline__cuerpo">' +
              '<p class="timeline__detalle">' + escHtml(item.detalles || 'Sin detalle') + '</p>' +
              '<p class="timeline__meta">' + escHtml(cuando) + ' · ' + escHtml(item.autor) + '</p>' +
            '</div>' +
          '</li>';
        }).join('');
      })
      .catch(function() {
        lista.innerHTML = '<li class="timeline__vacio">No se pudo cargar el historial</li>';
      });
  }

  // Las acciones en lote pueden omitir citas por choque de agenda:
  // se avisa una por una para que quede claro que paso con cada una.
  function mostrarAvisos(res) {
    if (!res.avisos || res.avisos.length === 0) return;
    res.avisos.forEach(function(aviso, i) {
      setTimeout(function() { showToast(aviso, 'warning'); }, 400 * (i + 1));
    });
  }

  // Tras cualquier accion: se limpia la pantalla y se refresca el registro.
  function afterContingencyAction() {
    resetContingencyForm();
    loadContingencyHistory();
    loadReservas();
  }

  function fetchContingencyAppointments() {
    var estId = document.getElementById('contEsteticista').value;
    var fecha = document.getElementById('contFecha').value;
    if (!estId || !fecha) {
      showToast('Selecciona especialista y fecha', 'warning');
      return;
    }

    contCitasBody.innerHTML = '<tr><td colspan="6" class="dashboard__empty">Consultando citas afectadas...</td></tr>';
    contResultados.style.display = 'block';

    fetch('api/admin/contingency.php?esteticista_id=' + encodeURIComponent(estId) + '&fecha=' + encodeURIComponent(fecha))
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success) {
          contCitasBody.innerHTML = '<tr><td colspan="6" class="dashboard__empty">' + escHtml(data.error) + '</td></tr>';
          return;
        }

        contTotalAfectadas.textContent = data.total;

        // El destino se rellena con lo que devuelve la API, que ya excluye al
        // especialista ausente: reasignarle sus propias citas no tiene sentido.
        var destino = document.getElementById('contNuevoEsteticista');
        if (destino) {
          var disponibles = data.otros_esteticistas || data.esteticistas || [];
          destino.innerHTML = '<option value="">Reasignar a especialista...</option>' +
            disponibles.map(function(e) {
              return '<option value="' + e.id_usuario + '">' + escHtml(e.nombre) + '</option>';
            }).join('');

          if (disponibles.length === 0) {
            destino.innerHTML = '<option value="">No hay otros especialistas disponibles</option>';
          }
        }

        if (!data.reservas || data.reservas.length === 0) {
          contCitasBody.innerHTML = '<tr><td colspan="6" class="dashboard__empty">No hay citas activas para este especialista en esta fecha</td></tr>';
          return;
        }

        contCitasBody.innerHTML = data.reservas.map(function(r) {
          var hora = r.fecha_hora_inicio.substring(11, 16);
          return '<tr>' +
            '<td><input type="checkbox" class="cont-checkbox" value="' + r.id_reserva + '"></td>' +
            '<td>#' + r.id_reserva + '</td>' +
            '<td>' + hora + '</td>' +
            '<td>' + escHtml(r.nombre_cliente) + ' (' + escHtml(r.telefono_cliente || 'Sin tel') + ')</td>' +
            '<td>' + escHtml(r.nombre_servicio) + '</td>' +
            '<td><span class="status-badge status-badge--confirmada">' + escHtml(r.nombre_estado) + '</span></td>' +
          '</tr>';
        }).join('');
      });
  }

  var btnRefrescarHistorial = document.getElementById('btnRefrescarHistorial');
  if (btnRefrescarHistorial) {
    btnRefrescarHistorial.addEventListener('click', loadContingencyHistory);
  }

  if (btnConsultarContingencia) {
    btnConsultarContingencia.addEventListener('click', fetchContingencyAppointments);
  }

  if (contSelectAll) {
    contSelectAll.addEventListener('change', function() {
      var checks = contCitasBody.querySelectorAll('.cont-checkbox');
      for (var i = 0; i < checks.length; i++) {
        checks[i].checked = contSelectAll.checked;
      }
    });
  }

  function getSelectedContCheckboxes() {
    var checks = contCitasBody.querySelectorAll('.cont-checkbox:checked');
    var ids = [];
    for (var i = 0; i < checks.length; i++) {
      ids.push(parseInt(checks[i].value, 10));
    }
    return ids;
  }

  if (btnReasignarLote) {
    btnReasignarLote.addEventListener('click', function() {
      var ids = getSelectedContCheckboxes();
      var newEstId = parseInt(document.getElementById('contNuevoEsteticista').value, 10);

      if (ids.length === 0) {
        showToast('Selecciona al menos una cita', 'warning');
        return;
      }
      if (!newEstId) {
        showToast('Selecciona el nuevo especialista destino', 'warning');
        return;
      }

      fetch('api/admin/contingency.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'reassign_bulk',
          reserva_ids: ids,
          nuevo_esteticista_id: newEstId,
          csrf_token: csrfToken
        })
      })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (res.success) {
          showToast(res.message, res.reasignadas > 0 ? 'success' : 'warning');
          mostrarAvisos(res);
          afterContingencyAction();
        } else {
          showToast(res.error || 'Error al reasignar', 'error');
        }
      });
    });
  }

  if (btnCancelarLote) {
    btnCancelarLote.addEventListener('click', function() {
      var ids = getSelectedContCheckboxes();
      if (ids.length === 0) {
        showToast('Selecciona al menos una cita', 'warning');
        return;
      }
      if (confirm('¿Confirmas cancelar ' + ids.length + ' citas por contingencia?')) {
        fetch('api/admin/contingency.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'cancel_bulk', reserva_ids: ids, csrf_token: csrfToken })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
          if (res.success) {
            showToast(res.message, 'info');
            afterContingencyAction();
          } else {
            showToast(res.error || 'Error al cancelar', 'error');
          }
        });
      }
    });
  }

  if (btnBloquearDiaContingencia) {
    btnBloquearDiaContingencia.addEventListener('click', function() {
      var estId = document.getElementById('contEsteticista').value;
      var fecha = document.getElementById('contFecha').value;
      if (!estId || !fecha) return;

      if (confirm('¿Deseas bloquear todo el día para este esteticista?')) {
        fetch('api/admin/contingency.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'block_day',
            esteticista_id: estId,
            fecha: fecha,
            motivo: 'Incapacidad o ausencia imprevista del especialista',
            csrf_token: csrfToken
          })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
          if (res.success) {
            showToast(res.message || 'Día bloqueado con éxito', 'success');
            afterContingencyAction();
          } else {
            showToast(res.error || 'Error al bloquear día', 'error');
          }
        });
      }
    });
  }

  // ============================================================
  // MODAL: GESTIÓN DE SERVICIOS
  // ============================================================
  var btnOpenServicios    = document.getElementById('btnOpenServicios');
  var formServicioCrud    = document.getElementById('formServicioCrud');
  var tablaServiciosBody  = document.getElementById('tablaServiciosBody');
  var crudServicioId      = document.getElementById('crudServicioId');
  var crudServicioTitle   = document.getElementById('crudServicioTitle');
  var btnCancelServicioEdit = document.getElementById('btnCancelServicioEdit');

  function loadServiciosCrud() {
    if (!tablaServiciosBody) return;
    tablaServiciosBody.innerHTML = '<tr><td colspan="6" class="dashboard__empty">Cargando catálogo...</td></tr>';

    fetch('api/admin/services.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success || !data.servicios) return;

        tablaServiciosBody.innerHTML = data.servicios.map(function(s) {
          var actBadge = s.activo == 1 ?
            '<span class="status-badge status-badge--confirmada">Activo</span>' :
            '<span class="status-badge status-badge--cancelada">Inactivo</span>';

          return '<tr>' +
            '<td>#' + s.id_servicio + '</td>' +
            '<td><strong>' + escHtml(s.nombre_servicio) + '</strong><br><small class="cell-muted">' + escHtml(s.nombre_categoria) + ' / ' + escHtml(s.nombre_subcategoria) + '</small></td>' +
            '<td>' + s.duracion_minutos + ' min</td>' +
            '<td>' + formatMoney(s.precio) + '</td>' +
            '<td>' + actBadge + '</td>' +
            '<td>' +
              '<button type="button" class="btn btn--outline btn--xs btn-edit-serv" data-json=\'' + JSON.stringify(s) + '\'>✏️ Editar</button> ' +
              '<button type="button" class="btn btn--outline btn--xs btn-toggle-serv" data-id="' + s.id_servicio + '">' + (s.activo == 1 ? 'Desactivar' : 'Activar') + '</button>' +
            '</td>' +
          '</tr>';
        }).join('');

        tablaServiciosBody.querySelectorAll('.btn-edit-serv').forEach(function(btn) {
          btn.addEventListener('click', function() {
            var s = JSON.parse(this.dataset.json);
            crudServicioId.value = s.id_servicio;
            document.getElementById('crudServNombre').value = s.nombre_servicio;
            document.getElementById('crudServPrecio').value = s.precio;
            document.getElementById('crudServDuracion').value = s.duracion_minutos;
            document.getElementById('crudServSubcat').value = s.id_subcategoria;
            document.getElementById('crudServDesc').value = s.descripcion || '';
            crudServicioTitle.textContent = 'Editar Tratamiento #' + s.id_servicio;
            btnCancelServicioEdit.style.display = 'inline-block';
          });
        });

        tablaServiciosBody.querySelectorAll('.btn-toggle-serv').forEach(function(btn) {
          btn.addEventListener('click', function() {
            var sId = parseInt(this.dataset.id, 10);
            fetch('api/admin/services.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ action: 'toggle', id_servicio: sId, csrf_token: csrfToken })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
              if (res.success) {
                showToast(res.message, 'success');
                loadServiciosCrud();
              }
            });
          });
        });
      });
  }

  if (btnOpenServicios) {
    btnOpenServicios.addEventListener('click', function() {
      loadServiciosCrud();
    });
  }

  if (btnCancelServicioEdit) {
    btnCancelServicioEdit.addEventListener('click', function() {
      formServicioCrud.reset();
      crudServicioId.value = '';
      crudServicioTitle.textContent = 'Nuevo Tratamiento';
      btnCancelServicioEdit.style.display = 'none';
    });
  }

  if (formServicioCrud) {
    formServicioCrud.addEventListener('submit', function(e) {
      e.preventDefault();
      var id = crudServicioId.value ? parseInt(crudServicioId.value, 10) : 0;
      var payload = {
        action: id ? 'update' : 'create',
        id_servicio: id || undefined,
        nombre: document.getElementById('crudServNombre').value.trim(),
        precio: parseFloat(document.getElementById('crudServPrecio').value),
        duracion_minutos: parseInt(document.getElementById('crudServDuracion').value, 10),
        subcategoria_id: parseInt(document.getElementById('crudServSubcat').value, 10),
        descripcion: document.getElementById('crudServDesc').value.trim(),
        csrf_token: csrfToken
      };

      fetch('api/admin/services.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (res.success) {
          showToast(res.message, 'success');
          formServicioCrud.reset();
          crudServicioId.value = '';
          crudServicioTitle.textContent = 'Nuevo Tratamiento';
          btnCancelServicioEdit.style.display = 'none';
          loadServiciosCrud();
        } else {
          showToast(res.error || 'Error al guardar', 'error');
        }
      });
    });
  }

  // ============================================================
  // MODAL: GESTIÓN DE PERSONAL
  // ============================================================
  var btnOpenPersonal     = document.getElementById('btnOpenPersonal');
  var formPersonalCrud    = document.getElementById('formPersonalCrud');
  var tablaPersonalBody   = document.getElementById('tablaPersonalBody');
  var crudStaffId         = document.getElementById('crudStaffId');
  var crudStaffTitle      = document.getElementById('crudStaffTitle');
  var btnCancelStaffEdit  = document.getElementById('btnCancelStaffEdit');
  var passHelpText        = document.getElementById('passHelpText');

  function loadPersonalCrud() {
    if (!tablaPersonalBody) return;
    tablaPersonalBody.innerHTML = '<tr><td colspan="6" class="dashboard__empty">Cargando empleados...</td></tr>';

    fetch('api/admin/employees.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success || !data.staff) return;

        tablaPersonalBody.innerHTML = data.staff.map(function(u) {
          var actBadge = u.estado_cuenta == 1 ?
            '<span class="status-badge status-badge--confirmada">Activo</span>' :
            '<span class="status-badge status-badge--cancelada">Inactivo</span>';

          var roleBadge = '<span class="user-pill__badge user-pill__badge--' + escHtml(u.nombre_rol.toLowerCase()) + '">' + escHtml(u.nombre_rol) + '</span>';

          return '<tr>' +
            '<td>#' + u.id_usuario + '</td>' +
            '<td><strong>' + escHtml(u.nombre) + '</strong><br><small class="cell-muted">📞 ' + escHtml(u.telefono || 'Sin tel') + '</small></td>' +
            '<td>' + escHtml(u.correo) + '</td>' +
            '<td>' + roleBadge + '</td>' +
            '<td>' + actBadge + '</td>' +
            '<td>' +
              '<button type="button" class="btn btn--outline btn--xs btn-edit-staff" data-json=\'' + JSON.stringify(u) + '\'>✏️ Editar</button> ' +
              '<button type="button" class="btn btn--outline btn--xs btn-toggle-staff" data-id="' + u.id_usuario + '">' + (u.estado_cuenta == 1 ? 'Desactivar' : 'Activar') + '</button>' +
            '</td>' +
          '</tr>';
        }).join('');

        tablaPersonalBody.querySelectorAll('.btn-edit-staff').forEach(function(btn) {
          btn.addEventListener('click', function() {
            var u = JSON.parse(this.dataset.json);
            crudStaffId.value = u.id_usuario;
            document.getElementById('crudStaffNombre').value = u.nombre;
            document.getElementById('crudStaffCorreo').value = u.correo;
            document.getElementById('crudStaffRol').value = u.id_rol;
            document.getElementById('crudStaffTelefono').value = u.telefono || '';
            document.getElementById('crudStaffPassword').value = '';
            crudStaffTitle.textContent = 'Editar Empleado #' + u.id_usuario;
            passHelpText.textContent = '(dejar vacío para no cambiar)';
            btnCancelStaffEdit.style.display = 'inline-block';
          });
        });

        tablaPersonalBody.querySelectorAll('.btn-toggle-staff').forEach(function(btn) {
          btn.addEventListener('click', function() {
            var uId = parseInt(this.dataset.id, 10);
            fetch('api/admin/employees.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ action: 'toggle', id_usuario: uId, csrf_token: csrfToken })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
              if (res.success) {
                showToast(res.message, 'success');
                loadPersonalCrud();
              } else {
                showToast(res.error || 'Error al cambiar estado', 'error');
              }
            });
          });
        });
      });
  }

  if (btnOpenPersonal) {
    btnOpenPersonal.addEventListener('click', function() {
      loadPersonalCrud();
    });
  }

  if (btnCancelStaffEdit) {
    btnCancelStaffEdit.addEventListener('click', function() {
      formPersonalCrud.reset();
      crudStaffId.value = '';
      crudStaffTitle.textContent = 'Nuevo Empleado';
      passHelpText.textContent = '(requerida al crear)';
      btnCancelStaffEdit.style.display = 'none';
    });
  }

  if (formPersonalCrud) {
    formPersonalCrud.addEventListener('submit', function(e) {
      e.preventDefault();
      var id = crudStaffId.value ? parseInt(crudStaffId.value, 10) : 0;
      var pass = document.getElementById('crudStaffPassword').value;

      if (!id && !pass) {
        showToast('La contraseña es obligatoria para nuevo personal', 'warning');
        return;
      }

      var payload = {
        action: id ? 'update' : 'create',
        id_usuario: id || undefined,
        nombre: document.getElementById('crudStaffNombre').value.trim(),
        correo: document.getElementById('crudStaffCorreo').value.trim(),
        id_rol: parseInt(document.getElementById('crudStaffRol').value, 10),
        telefono: document.getElementById('crudStaffTelefono').value.trim(),
        password: pass || undefined,
        csrf_token: csrfToken
      };

      fetch('api/admin/employees.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (res.success) {
          showToast(res.message, 'success');
          formPersonalCrud.reset();
          crudStaffId.value = '';
          crudStaffTitle.textContent = 'Nuevo Empleado';
          passHelpText.textContent = '(requerida al crear)';
          btnCancelStaffEdit.style.display = 'none';
          loadPersonalCrud();
        } else {
          showToast(res.error || 'Error al guardar empleado', 'error');
        }
      });
    });
  }

  // ============================================================
  // MODAL: LOGS DE AUDITORÍA (SUPERADMIN)
  // ============================================================
  var btnOpenLogs    = document.getElementById('btnOpenLogs');
  var tablaLogsBody  = document.getElementById('tablaLogsBody');

  function loadAuditLogs() {
    if (!tablaLogsBody) return;
    tablaLogsBody.innerHTML = '<tr><td colspan="6" class="dashboard__empty">Cargando registros...</td></tr>';

    fetch('api/admin/logs.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success || !data.logs || data.logs.length === 0) {
          tablaLogsBody.innerHTML = '<tr><td colspan="6" class="dashboard__empty">No hay registros de auditoría</td></tr>';
          return;
        }

        tablaLogsBody.innerHTML = data.logs.map(function(l) {
          var f = new Date(l.fecha_hora.replace(/-/g, '/'));
          var fStr = isNaN(f.getTime()) ? l.fecha_hora : f.toLocaleString('es-CO');

          return '<tr>' +
            '<td>#' + l.id_log + '</td>' +
            '<td>' + fStr + '</td>' +
            '<td><strong>' + escHtml(l.nombre_usuario) + '</strong><br><small class="cell-muted">' + escHtml(l.correo_usuario) + '</small></td>' +
            '<td><span class="user-pill__badge user-pill__badge--' + escHtml(l.nombre_rol.toLowerCase()) + '">' + escHtml(l.nombre_rol) + '</span></td>' +
            '<td><code>' + escHtml(l.accion) + '</code></td>' +
            '<td><span class="status-badge" style="background:#eee;color:#444;">' + escHtml(l.tabla_afectada) + '</span></td>' +
          '</tr>';
        }).join('');
      })
      .catch(function() {
        tablaLogsBody.innerHTML = '<tr><td colspan="6" class="dashboard__empty">Error al cargar logs</td></tr>';
      });
  }

  if (btnOpenLogs) {
    btnOpenLogs.addEventListener('click', function() {
      loadAuditLogs();
    });
  }

  // ============================================================
  // MODAL: CORREOS ENVIADOS
  // ============================================================
  var btnOpenCorreos    = document.getElementById('btnOpenCorreos');
  var tablaCorreosBody  = document.getElementById('tablaCorreosBody');
  var correoPreview     = document.getElementById('correoPreview');
  var correoPreviewFrm  = document.getElementById('correoPreviewFrame');
  var correoPreviewAsun = document.getElementById('correoPreviewAsunto');
  var btnCerrarPreview  = document.getElementById('btnCerrarPreview');
  var correosTransporte = document.getElementById('correosTransporte');

  function loadCorreos() {
    if (!tablaCorreosBody) return;
    tablaCorreosBody.innerHTML = '<tr><td colspan="6" class="dashboard__empty">Cargando correos...</td></tr>';

    fetch('api/admin/emails.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success) {
          tablaCorreosBody.innerHTML = '<tr><td colspan="6" class="dashboard__empty">' + escHtml(data.error || 'Error al cargar') + '</td></tr>';
          return;
        }

        if (correosTransporte) {
          correosTransporte.textContent = data.transport === 'smtp'
            ? 'Envío SMTP activo'
            : 'Modo registro (sin envío real)';
        }

        if (!data.correos || data.correos.length === 0) {
          tablaCorreosBody.innerHTML = '<tr><td colspan="6" class="dashboard__empty">Todavía no se ha enviado ningún correo</td></tr>';
          return;
        }

        tablaCorreosBody.innerHTML = data.correos.map(function(c) {
          var esError = (c.estado || '').indexOf('error') === 0;
          var badge = '<span class="status-badge status-badge--' + (esError ? 'cancelada' : 'confirmada') + '">' +
                      escHtml(c.estado) + '</span>';

          return '<tr>' +
            '<td data-label="#">#' + c.id_correo + '</td>' +
            '<td data-label="Fecha y hora">' + escHtml(c.enviado_en) + '</td>' +
            '<td data-label="Destinatario">' + escHtml(c.destinatario) + '</td>' +
            '<td data-label="Asunto">' + escHtml(c.asunto) + '</td>' +
            '<td data-label="Estado">' + badge + '</td>' +
            '<td data-label="Acciones">' +
              '<button type="button" class="btn btn--outline btn--xs btn-ver-correo" data-id="' + c.id_correo + '">Ver</button>' +
            '</td>' +
          '</tr>';
        }).join('');

        tablaCorreosBody.querySelectorAll('.btn-ver-correo').forEach(function(btn) {
          btn.addEventListener('click', function() {
            var cId = this.dataset.id;
            fetch('api/admin/emails.php?id=' + encodeURIComponent(cId))
              .then(function(r) { return r.json(); })
              .then(function(res) {
                if (!res.success) {
                  showToast(res.error || 'No se pudo cargar el correo', 'error');
                  return;
                }
                if (correoPreviewAsun) correoPreviewAsun.textContent = res.correo.asunto;
                if (correoPreviewFrm) correoPreviewFrm.srcdoc = res.correo.cuerpo_html;
                if (correoPreview) correoPreview.style.display = 'block';
              });
          });
        });
      })
      .catch(function() {
        tablaCorreosBody.innerHTML = '<tr><td colspan="6" class="dashboard__empty">Error de conexión</td></tr>';
      });
  }

  if (btnOpenCorreos) {
    btnOpenCorreos.addEventListener('click', function() {
      if (correoPreview) correoPreview.style.display = 'none';
      loadCorreos();
    });
  }

  if (btnCerrarPreview) {
    btnCerrarPreview.addEventListener('click', function() {
      if (correoPreview) correoPreview.style.display = 'none';
      if (correoPreviewFrm) correoPreviewFrm.srcdoc = '';
    });
  }

  // ============================================================
  // MODAL: GENERAR INFORME IMPRIMIBLE
  // ============================================================
  var btnOpenReporte       = document.getElementById('btnOpenReporte');
  var btnRecalcularReporte = document.getElementById('btnRecalcularReporte');
  var btnImprimirReporte   = document.getElementById('btnImprimirReporte');

  function renderReportMetrics(data) {
    var m = data.metrics || {};
    var totales = m.total_citas || 0;
    var reales  = m.ingresos_reales || 0;
    var proyect = m.ingresos_proyectados || 0;
    var estados = m.por_estado || {};

    var completadas = estados['Completada'] || 0;
    var efectividad = totales > 0 ? Math.round((completadas / totales) * 100) : 0;

    // Actualizar KPI Cards
    document.getElementById('kpiTotalCitas').textContent = totales;
    document.getElementById('kpiIngresosReales').textContent = formatMoney(reales);
    document.getElementById('kpiIngresosProyectados').textContent = formatMoney(proyect);
    document.getElementById('kpiTasaEfectividad').textContent = efectividad + '%';

    // Período
    var repPeriodoLabel = document.getElementById('repPeriodoLabel');
    if (repPeriodoLabel && data.periodo) {
      repPeriodoLabel.textContent = data.periodo.desde + ' hasta ' + data.periodo.hasta;
    }

    // Desglose Estados
    var estadosBox = document.getElementById('repEstadosContainer');
    if (estadosBox) {
      var estHtml = '<div style="display:grid; grid-template-columns: repeat(2, 1fr); gap: 6px;">';
      for (var st in estados) {
        if (estados.hasOwnProperty(st)) {
          var cls = estadoClasses[st] || 'pendiente';
          estHtml += '<div style="display:flex; justify-content:space-between; padding:4px 8px; background:rgba(0,0,0,0.03); border-radius:4px;">' +
            '<span class="status-badge status-badge--' + cls + '">' + escHtml(st) + '</span>' +
            '<strong>' + estados[st] + '</strong>' +
          '</div>';
        }
      }
      estHtml += '</div>';
      estadosBox.innerHTML = estHtml;
    }

    // Desglose Especialistas
    var espBox = document.getElementById('repEspecialistasContainer');
    if (espBox && m.por_especialista) {
      espBox.innerHTML = m.por_especialista.map(function(e) {
        return '<div style="display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px dashed rgba(0,0,0,0.08); font-size:0.85rem;">' +
          '<span>' + escHtml(e.especialista) + '</span>' +
          '<span><strong>' + e.citas + ' citas</strong> (' + formatMoney(e.ingresos) + ')</span>' +
        '</div>';
      }).join('') || '<p class="cell-muted">Sin citas en el período</p>';
    }

    // Desglose Servicios
    var servBox = document.getElementById('repServiciosContainer');
    if (servBox && m.por_servicio) {
      servBox.innerHTML = '<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px;">' +
        m.por_servicio.map(function(s) {
          return '<div style="padding:8px 12px; background:rgba(0,0,0,0.02); border-left:3px solid var(--color-olive); border-radius:4px;">' +
            '<strong>' + escHtml(s.nombre_servicio) + '</strong><br>' +
            '<span style="font-size:0.8rem; color:#666;">' + s.citas + ' citas · ' + formatMoney(s.ingresos) + '</span>' +
          '</div>';
        }).join('') + '</div>';
    }

    // Tabla de Citas del Período
    var tablaCitasBody = document.getElementById('tablaReporteCitasBody');
    if (tablaCitasBody) {
      if (!data.citas || data.citas.length === 0) {
        tablaCitasBody.innerHTML = '<tr><td colspan="7" class="dashboard__empty">No se encontraron citas en este período</td></tr>';
      } else {
        tablaCitasBody.innerHTML = data.citas.map(function(c) {
          var cls = estadoClasses[c.nombre_estado] || 'pendiente';
          return '<tr>' +
            '<td>#' + c.id_reserva + '</td>' +
            '<td>' + c.fecha_hora_inicio + '</td>' +
            '<td>' + escHtml(c.nombre_cliente) + '</td>' +
            '<td>' + escHtml(c.nombre_servicio) + '</td>' +
            '<td>' + escHtml(c.nombre_esteticista) + '</td>' +
            '<td>' + formatMoney(c.precio) + '</td>' +
            '<td><span class="status-badge status-badge--' + cls + '">' + escHtml(c.nombre_estado) + '</span></td>' +
          '</tr>';
        }).join('');
      }
    }
  }

  function fetchReportData() {
    var d = document.getElementById('repDesde').value;
    var h = document.getElementById('repHasta').value;
    var params = new URLSearchParams();
    if (d) params.set('desde', d);
    if (h) params.set('hasta', h);

    fetch('api/admin/report.php?' + params.toString())
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          renderReportMetrics(data);
        } else {
          showToast(data.error || 'Error al generar reporte', 'error');
        }
      })
      .catch(function() {
        showToast('Error al conectar para generar informe', 'error');
      });
  }

  if (btnOpenReporte) {
    btnOpenReporte.addEventListener('click', function() {
      fetchReportData();
    });
  }

  if (btnRecalcularReporte) {
    btnRecalcularReporte.addEventListener('click', fetchReportData);
  }

  // Tarea 3: Impresión limpia
  if (btnImprimirReporte) {
    btnImprimirReporte.addEventListener('click', function() {
      window.print();
    });
  }

  // Cierre de sesión
  var logoutBtn = document.getElementById('dashLogoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function() {
      fetch('api/auth/logout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ csrf_token: csrfToken })
      })
      .then(function() { window.location.href = 'index.php'; })
      .catch(function() { window.location.href = 'index.php'; });
    });
  }

  // Carga inicial
  loadReservas();

})();
