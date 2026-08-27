(function() {
  'use strict';

  var csrfToken = document.getElementById('dashCsrf').value;
  var tbody = document.getElementById('reservasBody');

  var estadoClasses = {
    'Pendiente': 'pendiente', 'Confirmada': 'confirmada', 'Completada': 'completada',
    'Cancelada': 'cancelada', 'Reasignada': 'reasignada', 'No_Show': 'no_show'
  };

  var estadoNames = ['', 'Pendiente', 'Confirmada', 'Completada', 'Cancelada', 'Reasignada', 'No Show'];

  function showToast(msg, type) {
    var container = document.getElementById('toastContainer');
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

  function escHtml(str) {
    var div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
  }

  function loadReservas() {
    var params = new URLSearchParams();
    var fecha = document.getElementById('filterFecha').value;
    var estado = document.getElementById('filterEstado').value;
    var esteticista = document.getElementById('filterEsteticista').value;

    if (fecha) params.set('fecha', fecha);
    if (estado) params.set('estado', estado);
    if (esteticista) params.set('esteticista', esteticista);

    var url = 'api/appointments/all.php' + (params.toString() ? '?' + params : '');

    fetch(url)
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.success) {
          tbody.innerHTML = '<tr><td colspan="8" class="dashboard__empty">' + escHtml(data.error || 'Error') + '</td></tr>';
          return;
        }

        if (data.reservas.length === 0) {
          tbody.innerHTML = '<tr><td colspan="8" class="dashboard__empty">No hay reservas con estos filtros</td></tr>';
          return;
        }

        tbody.innerHTML = data.reservas.map(function(r) {
          var fechaInicio = new Date(r.fecha_hora_inicio);
          var fechaStr = fechaInicio.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
          var horaStr = fechaInicio.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', hour12: true });
          var cls = estadoClasses[r.nombre_estado] || 'pendiente';

          var selectHtml = '<select class="dashboard__status-select" data-reserva="' + r.id_reserva + '">';
          for (var i = 1; i <= 6; i++) {
            selectHtml += '<option value="' + i + '"' + (r.id_estado == i ? ' selected' : '') + '>' + estadoNames[i] + '</option>';
          }
          selectHtml += '</select>';

          return '<tr>' +
            '<td>' + r.id_reserva + '</td>' +
            '<td>' + escHtml(r.nombre_cliente) + '<br><small style="color:#999">' + escHtml(r.correo_cliente) + '</small></td>' +
            '<td>' + escHtml(r.nombre_servicio) + '</td>' +
            '<td>' + escHtml(r.nombre_esteticista) + '</td>' +
            '<td>' + fechaStr + '</td>' +
            '<td>' + horaStr + '</td>' +
            '<td><span class="status-badge status-badge--' + cls + '">' + escHtml(r.nombre_estado) + '</span></td>' +
            '<td>' + selectHtml + '</td>' +
          '</tr>';
        }).join('');

        tbody.querySelectorAll('.dashboard__status-select').forEach(function(sel) {
          sel.addEventListener('change', function() {
            updateStatus(parseInt(sel.dataset.reserva), parseInt(sel.value));
          });
        });
      })
      .catch(function() {
        tbody.innerHTML = '<tr><td colspan="8" class="dashboard__empty">Error de conexion</td></tr>';
      });
  }

  function updateStatus(reservaId, estadoId) {
    fetch('api/appointments/update-status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ reserva_id: reservaId, estado_id: estadoId, csrf_token: csrfToken })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        showToast(data.message, 'success');
        loadReservas();
      } else {
        showToast(data.error || 'Error al actualizar', 'error');
      }
    })
    .catch(function() {
      showToast('Error de conexion', 'error');
    });
  }

  document.getElementById('filterFecha').addEventListener('change', loadReservas);
  document.getElementById('filterEstado').addEventListener('change', loadReservas);
  document.getElementById('filterEsteticista').addEventListener('change', loadReservas);

  document.getElementById('dashLogoutBtn').addEventListener('click', function() {
    fetch('api/auth/logout.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf_token: csrfToken })
    })
    .then(function() { window.location.href = 'index.php'; })
    .catch(function() { window.location.href = 'index.php'; });
  });

  loadReservas();
})();
