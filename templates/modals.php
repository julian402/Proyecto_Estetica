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
          <input type="password" class="form-input" id="regPassword" name="password" minlength="12" maxlength="128" autocomplete="new-password" placeholder="Minimo 12 caracteres" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="regPasswordConfirm">Confirmar contrasena</label>
          <input type="password" class="form-input" id="regPasswordConfirm" name="password_confirm" minlength="12" maxlength="128" autocomplete="new-password" placeholder="Repite la contrasena" required>
        </div>
        <p class="modal__error" id="registerError" style="color: #e74c3c; font-size: 0.875rem; display: none;"></p>
        <button type="submit" class="btn btn--primary btn--full">Registrarse</button>
      </form>
      <div class="modal__links">
        <p>Ya tienes cuenta? <a href="#" data-switch-modal="loginModal">Inicia sesion</a></p>
      </div>
    </div>
  </div>

  <!-- ========== MODAL MIS CITAS ========== -->
  <div class="modal" id="citasModal">
    <div class="modal__content modal__content--wide">
      <button class="modal__close" data-close-modal>&times;</button>
      <h2 class="modal__title">Mis citas</h2>
      <div id="citasList" class="modal__scroll">
        <p style="text-align:center; color:#999;">Cargando...</p>
      </div>
    </div>
  </div>

  <!-- ========== MODAL MI PERFIL ========== -->
  <div class="modal" id="perfilModal">
    <div class="modal__content">
      <button class="modal__close" data-close-modal>&times;</button>
      <h2 class="modal__title">Mi perfil</h2>
      <form class="modal__form" id="perfilForm">
        <div class="form-group">
          <label class="form-label" for="perfilNombre">Nombre</label>
          <input type="text" class="form-input" id="perfilNombre" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="perfilCorreo">Correo</label>
          <input type="email" class="form-input" id="perfilCorreo" disabled>
        </div>
        <div class="form-group">
          <label class="form-label" for="perfilTelefono">Telefono</label>
          <input type="tel" class="form-input" id="perfilTelefono" placeholder="300 123 4567">
        </div>
        <div class="user-menu__divider" style="margin: 16px 0;"></div>
        <p style="font-size: 0.8rem; color: #999; margin-bottom: 8px;">Cambiar contrasena (opcional)</p>
        <div class="form-group">
          <label class="form-label" for="perfilPassActual">Contrasena actual</label>
          <input type="password" class="form-input" id="perfilPassActual" placeholder="Solo si deseas cambiarla">
        </div>
        <div class="form-group">
          <label class="form-label" for="perfilPassNueva">Nueva contrasena</label>
          <input type="password" class="form-input" id="perfilPassNueva" placeholder="Minimo 6 caracteres">
        </div>
        <p class="modal__error" id="perfilError" style="color: #e74c3c; font-size: 0.875rem; display: none;"></p>
        <button type="submit" class="btn btn--primary btn--full">Guardar cambios</button>
      </form>
    </div>
  </div>

  <!-- ========== MODAL FAVORITOS ========== -->
  <div class="modal" id="favoritosModal">
    <div class="modal__content modal__content--wide">
      <button class="modal__close" data-close-modal>&times;</button>
      <h2 class="modal__title">Tratamientos favoritos</h2>
      <div id="favoritosList" class="modal__scroll">
        <p style="text-align:center; color:#999;">Cargando...</p>
      </div>
    </div>
  </div>
