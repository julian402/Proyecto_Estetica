        <div class="user-menu" id="userMenu">
          <button class="user-menu__toggle" aria-label="Menu de usuario" id="userMenuToggle" aria-expanded="false">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </button>
          <div class="user-menu__dropdown" id="userMenuDropdown">
            <div class="user-menu__guest" id="userMenuGuest" <?php if (is_logged_in()): ?>style="display:none"<?php endif; ?>>
              <button class="user-menu__item" data-open-modal="loginModal">Iniciar sesion</button>
              <button class="user-menu__item" data-open-modal="registerModal">Registrarse</button>
            </div>
            <div class="user-menu__logged" id="userMenuLogged" <?php if (!is_logged_in()): ?>style="display:none"<?php endif; ?>>
              <?php if (is_logged_in() && $currentUser): ?>
                <span class="user-menu__name"><?php echo sanitize($currentUser['nombre']); ?></span>
                <div class="user-menu__divider"></div>
              <?php endif; ?>
              <button class="user-menu__item">Mis citas</button>
              <button class="user-menu__item">Mi perfil</button>
              <button class="user-menu__item">Tratamientos favoritos</button>
              <div class="user-menu__divider"></div>
              <button class="user-menu__item" id="logoutBtn">Cerrar sesion</button>
            </div>
          </div>
        </div>
