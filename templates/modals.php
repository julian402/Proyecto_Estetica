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
        <div class="form-group">
          <label class="form-label" for="regTelefono">Teléfono</label>
          <input type="tel" class="form-input" id="regTelefono" name="phone" placeholder="Ej: 300 123 4567"
                 pattern="[0-9+\s-]{7,15}" title="Ingresa un número de teléfono de al menos 7 dígitos"
                 autocomplete="tel" required>
        </div>
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
<?php require __DIR__ . '/modal-reschedule.php'; ?>
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
