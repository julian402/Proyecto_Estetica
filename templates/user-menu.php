<?php
// Con sesion iniciada el avatar lleva a su area (cliente o panel); sin ella
// despliega el menu de acceso. Las opciones del cliente ya no son ventanas.
$esPersonal = $currentUser && in_array((int) $currentUser['id_rol'], [2, 3, 4], true);
$destino    = $esPersonal ? 'dashboard.php' : 'cuenta.php';
?>
        <div class="user-menu" id="userMenu">
          <?php if (is_logged_in() && $currentUser): ?>
            <a href="<?php echo $destino; ?>" class="user-menu__toggle user-menu__toggle--link" id="userMenuLink"
               title="<?php echo $esPersonal ? 'Ir al panel' : 'Mi cuenta'; ?>"
               aria-label="<?php echo $esPersonal ? 'Ir al panel de administración' : 'Ir a mi cuenta'; ?>">
              <span class="user-menu__avatar" aria-hidden="true"><?php echo mb_strtoupper(mb_substr($currentUser['nombre'], 0, 1)); ?></span>
              <span class="user-menu__label"><?php echo sanitize(explode(' ', trim($currentUser['nombre']))[0]); ?></span>
            </a>
          <?php else: ?>
            <button class="user-menu__toggle" aria-label="Menu de usuario" id="userMenuToggle" aria-expanded="false">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
            </button>
          <?php endif; ?>

          <div class="user-menu__dropdown" id="userMenuDropdown">
            <div class="user-menu__guest" id="userMenuGuest" <?php if (is_logged_in()): ?>style="display:none"<?php endif; ?>>
              <button class="user-menu__item" data-open-modal="loginModal">Iniciar sesion</button>
              <button class="user-menu__item" data-open-modal="registerModal">Registrarse</button>
            </div>

            <!-- Tras iniciar sesion sin recargar, este bloque sustituye al de invitado -->
            <div class="user-menu__logged" id="userMenuLogged" <?php if (!is_logged_in()): ?>style="display:none"<?php endif; ?>>
              <?php if (is_logged_in() && $currentUser): ?>
                <span class="user-menu__name"><?php echo sanitize($currentUser['nombre']); ?></span>
                <div class="user-menu__divider"></div>
              <?php endif; ?>
              <a href="<?php echo $destino; ?>" class="user-menu__item" style="text-decoration:none; display:block;">
                <?php echo $esPersonal ? 'Panel de Reservas' : 'Mi cuenta'; ?>
              </a>
              <div class="user-menu__divider"></div>
              <button class="user-menu__item" id="logoutBtn">Cerrar sesion</button>
            </div>
          </div>
        </div>
