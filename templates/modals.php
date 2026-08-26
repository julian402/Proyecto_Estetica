  <!-- ========== MODAL LOGIN ========== -->
  <div class="modal" id="loginModal">
    <div class="modal__content">
      <button class="modal__close" data-close-modal>&times;</button>
      <h2 class="modal__title">Iniciar sesion</h2>
      <form class="modal__form" id="loginForm">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <div class="form-group">
          <label class="form-label" for="loginCorreo">Correo</label>
          <input type="email" class="form-input" id="loginCorreo" name="email" placeholder="tucorreo@ejemplo.com" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="loginPassword">Contrasena</label>
          <input type="password" class="form-input" id="loginPassword" name="password" placeholder="Tu contrasena" required>
        </div>
        <p class="modal__error" id="loginError" style="color: #e74c3c; font-size: 0.875rem; display: none;"></p>
        <button type="submit" class="btn btn--primary btn--full">Entrar</button>
      </form>
      <div class="modal__links">
        <p>No tienes cuenta? <a href="#" data-switch-modal="registerModal">Registrate</a></p>
        <p><a href="#">Olvidaste tu contrasena?</a></p>
      </div>
    </div>
  </div>

  <!-- ========== MODAL REGISTRO ========== -->
  <div class="modal" id="registerModal">
    <div class="modal__content">
      <button class="modal__close" data-close-modal>&times;</button>
      <h2 class="modal__title">Crear cuenta</h2>
      <form class="modal__form" id="registerForm">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <div class="form-group">
          <label class="form-label" for="regNombre">Nombre</label>
          <input type="text" class="form-input" id="regNombre" name="name" placeholder="Tu nombre" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="regCorreo">Correo</label>
          <input type="email" class="form-input" id="regCorreo" name="email" placeholder="tucorreo@ejemplo.com" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="regPassword">Contrasena</label>
          <input type="password" class="form-input" id="regPassword" name="password" placeholder="Crea una contrasena" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="regPasswordConfirm">Confirmar contrasena</label>
          <input type="password" class="form-input" id="regPasswordConfirm" name="password_confirm" placeholder="Repite la contrasena" required>
        </div>
        <p class="modal__error" id="registerError" style="color: #e74c3c; font-size: 0.875rem; display: none;"></p>
        <button type="submit" class="btn btn--primary btn--full">Registrarse</button>
      </form>
      <div class="modal__links">
        <p>Ya tienes cuenta? <a href="#" data-switch-modal="loginModal">Inicia sesion</a></p>
      </div>
    </div>
  </div>
