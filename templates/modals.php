  <!-- ========== MODAL LOGIN ========== -->
  <div class="modal" id="loginModal">
    <div class="modal__content">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <h2 class="modal__title">Iniciar sesión</h2>
      <form class="modal__form" id="loginForm">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <div class="form-group">
          <label class="form-label" for="loginCorreo">Correo electrónico</label>
          <input type="email" class="form-input" id="loginCorreo" name="email" placeholder="tucorreo@ejemplo.com" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="loginPassword">Contraseña</label>
          <input type="password" class="form-input" id="loginPassword" name="password" placeholder="Tu contraseña" required>
        </div>
        <p class="modal__error" id="loginError" style="color: #e74c3c; font-size: 0.875rem; display: none;"></p>
        <button type="submit" class="btn btn--primary btn--full">Entrar</button>
      </form>
      <div class="modal__links">
        <p>¿No tienes cuenta? <a href="#" data-switch-modal="registerModal">Regístrate</a></p>
      </div>
    </div>
  </div>

  <!-- ========== MODAL REGISTRO ========== -->
  <div class="modal" id="registerModal">
    <div class="modal__content">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <h2 class="modal__title">Crear cuenta</h2>
      <form class="modal__form" id="registerForm">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <div class="form-group">
          <label class="form-label" for="regNombre">Nombre completo</label>
          <input type="text" class="form-input" id="regNombre" name="name" placeholder="Tu nombre" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="regCorreo">Correo electrónico</label>
          <input type="email" class="form-input" id="regCorreo" name="email" placeholder="tucorreo@ejemplo.com" required>
        </div>
        <!-- Tarea 21: Reducir contraseña a mínimo 8 caracteres -->
        <div class="form-group">
          <label class="form-label" for="regPassword">Contraseña</label>
          <input type="password" class="form-input" id="regPassword" name="password" minlength="8" maxlength="128" autocomplete="new-password" placeholder="Mínimo 8 caracteres" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="regPasswordConfirm">Confirmar contraseña</label>
          <input type="password" class="form-input" id="regPasswordConfirm" name="password_confirm" minlength="8" maxlength="128" autocomplete="new-password" placeholder="Mínimo 8 caracteres" required>
        </div>
        <p class="modal__error" id="registerError" style="color: #e74c3c; font-size: 0.875rem; display: none;"></p>
        <button type="submit" class="btn btn--primary btn--full">Registrarse</button>
      </form>
      <div class="modal__links">
        <p>¿Ya tienes cuenta? <a href="#" data-switch-modal="loginModal">Inicia sesión</a></p>
      </div>
    </div>
  </div>

  <!-- ========== MODAL MIS CITAS ========== -->
  <div class="modal" id="citasModal">
    <div class="modal__content modal__content--wide">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <div class="modal__header-kbeauty">
        <h2 class="modal__title">Mis citas</h2>
        <p class="modal__subtitle">Consulta el estado de tus reservas, reprograma tus citas o gestiona tu agenda.</p>
      </div>
      <div id="citasList" class="modal__scroll">
        <p style="text-align:center; color:#999;">Cargando tus citas...</p>
      </div>
    </div>
  </div>

  <!-- ========== MODAL MI PERFIL ========== -->
  <div class="modal" id="perfilModal">
    <div class="modal__content">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <div class="modal__header-kbeauty">
        <div class="perfil-avatar" id="perfilAvatar">
          <span>HB</span>
        </div>
        <h2 class="modal__title" style="margin-bottom: 4px;">Mi perfil</h2>
        <p class="modal__subtitle">Gestiona tus datos personales y preferencias de cuenta.</p>
      </div>
      <form class="modal__form" id="perfilForm">
        <div class="form-group">
          <label class="form-label" for="perfilNombre">Nombre completo</label>
          <input type="text" class="form-input" id="perfilNombre" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="perfilCorreo">Correo electrónico</label>
          <input type="email" class="form-input" id="perfilCorreo" disabled title="El correo electrónico no puede ser modificado">
          <span style="font-size: 0.75rem; color: var(--color-text-muted); margin-top: 4px; display: block;">El correo es el identificador principal de tu cuenta.</span>
        </div>
        <div class="form-group">
          <label class="form-label" for="perfilTelefono">Teléfono de contacto</label>
          <input type="tel" class="form-input" id="perfilTelefono" placeholder="300 123 4567" pattern="[0-9+\s-]{7,15}">
        </div>
        <div class="user-menu__divider" style="margin: 20px 0 16px;"></div>
        <p style="font-size: 0.85rem; font-weight: 600; color: var(--color-text); margin-bottom: 12px;">Cambiar contraseña (opcional)</p>
        <div class="form-group">
          <label class="form-label" for="perfilPassActual">Contraseña actual</label>
          <input type="password" class="form-input" id="perfilPassActual" placeholder="Solo si deseas cambiarla">
        </div>
        <!-- Tarea 21: Reducir contraseña a mínimo 8 caracteres -->
        <div class="form-group">
          <label class="form-label" for="perfilPassNueva">Nueva contraseña</label>
          <input type="password" class="form-input" id="perfilPassNueva" minlength="8" maxlength="128" placeholder="Mínimo 8 caracteres">
        </div>
        <p class="modal__error" id="perfilError" style="color: #e74c3c; font-size: 0.875rem; display: none;"></p>
        <button type="submit" class="btn btn--primary btn--full">Guardar cambios</button>
      </form>
    </div>
  </div>

  <!-- ========== MODAL REPROGRAMAR CITA (CLIENTE) ========== -->
  <div class="modal" id="rescheduleModal">
    <div class="modal__content">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <div class="modal__header-kbeauty">
        <h2 class="modal__title">Reprogramar cita</h2>
        <p class="modal__subtitle">Elige una nueva fecha y horario para tu tratamiento facial.</p>
      </div>

      <!-- Resumen de cita actual -->
      <div class="reschedule-summary" id="rescheduleSummary">
        <div class="reschedule-summary__row">
          <span>Tratamiento:</span>
          <strong id="rescheduleServicioNombre">-</strong>
        </div>
        <div class="reschedule-summary__row">
          <span>Especialista:</span>
          <strong id="rescheduleEspecialistaNombre">-</strong>
        </div>
        <div class="reschedule-summary__row">
          <span>Cita actual:</span>
          <strong id="rescheduleActualFechaHora">-</strong>
        </div>
      </div>

      <form class="modal__form" id="rescheduleForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" id="rescheduleReservaId" name="reserva_id" value="">
        <input type="hidden" id="rescheduleServicioId" name="servicio_id" value="">
        <input type="hidden" id="rescheduleEsteticistaId" name="esteticista_id" value="">

        <div class="form-group">
          <label class="form-label" for="rescheduleFecha">
            Nueva fecha <span class="required-asterisk" aria-hidden="true">*</span>
          </label>
          <input type="date" class="form-input" id="rescheduleFecha" name="date" required aria-required="true">
        </div>

        <div class="form-group">
          <label class="form-label" for="rescheduleHora">
            Nueva hora disponible <span class="required-asterisk" aria-hidden="true">*</span>
          </label>
          <select class="form-select" id="rescheduleHora" name="time" required aria-required="true">
            <option value="">Selecciona fecha primero</option>
          </select>
        </div>

        <p class="modal__error" id="rescheduleError" style="color: #e74c3c; font-size: 0.875rem; display: none;"></p>
        <div class="modal__button-group">
          <button type="button" class="btn btn--outline" data-close-modal>Cancelar</button>
          <button type="submit" class="btn btn--primary" id="confirmRescheduleBtn">Confirmar cambio</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ========== MODAL COMPLETAR CUENTA (GUEST A CLIENTE) ========== -->
  <div class="modal" id="completeAccountModal">
    <div class="modal__content">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <div class="modal__header-kbeauty">
        <h2 class="modal__title">Completa tu cuenta</h2>
        <p class="modal__subtitle">Define tu contraseña para consultar tu historial, reprogramar tus citas y acceder a tus favoritos.</p>
      </div>

      <form class="modal__form" id="completeAccountForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div class="form-group">
          <label class="form-label" for="completeNombre">
            Nombre completo <span class="required-asterisk" aria-hidden="true">*</span>
          </label>
          <input type="text" class="form-input" id="completeNombre" name="nombre" placeholder="Tu nombre completo" required aria-required="true">
        </div>

        <div class="form-group">
          <label class="form-label" for="completeCorreo">
            Correo electrónico <span class="required-asterisk" aria-hidden="true">*</span>
          </label>
          <input type="email" class="form-input" id="completeCorreo" name="correo" placeholder="tu@correo.com" required aria-required="true">
        </div>

        <div class="form-group">
          <label class="form-label" for="completeTelefono">
            Teléfono <span class="required-asterisk" aria-hidden="true">*</span>
          </label>
          <input type="tel" class="form-input" id="completeTelefono" name="telefono" placeholder="300 123 4567" required aria-required="true" pattern="[0-9+\s-]{7,15}">
        </div>

        <!-- Tarea 21: Mínimo 8 caracteres -->
        <div class="form-group">
          <label class="form-label" for="completePassword">
            Contraseña <span class="required-asterisk" aria-hidden="true">*</span>
          </label>
          <input type="password" class="form-input" id="completePassword" name="password" minlength="8" maxlength="128" autocomplete="new-password" placeholder="Mínimo 8 caracteres" required aria-required="true">
        </div>

        <div class="form-group">
          <label class="form-label" for="completePasswordConfirm">
            Confirmar contraseña <span class="required-asterisk" aria-hidden="true">*</span>
          </label>
          <input type="password" class="form-input" id="completePasswordConfirm" name="password_confirm" minlength="8" maxlength="128" autocomplete="new-password" placeholder="Mínimo 8 caracteres" required aria-required="true">
        </div>

        <p class="modal__error" id="completeAccountError" style="color: #e74c3c; font-size: 0.875rem; display: none;"></p>
        <button type="submit" class="btn btn--primary btn--full" id="completeAccountBtn">Activar mi cuenta</button>
      </form>
      <div class="modal__links">
        <p>¿Ya tienes cuenta activa? <a href="#" data-switch-modal="loginModal">Inicia sesión</a></p>
      </div>
    </div>
  </div>

  <!-- ========== MODAL FAVORITOS ========== -->
  <div class="modal" id="favoritosModal">
    <div class="modal__content modal__content--wide">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <div class="modal__header-kbeauty">
        <h2 class="modal__title">Tratamientos favoritos</h2>
        <p class="modal__subtitle">Tus tratamientos guardados para agendar con un solo clic.</p>
      </div>
      <div id="favoritosList" class="modal__scroll">
        <p style="text-align:center; color:#999;">Cargando favoritos...</p>
      </div>
    </div>
  </div>
