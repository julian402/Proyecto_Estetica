<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hanul Beauty - Cuidado Facial Coreano en Bogota</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>

  <!-- ========== HEADER / NAV ========== -->
  <header class="header" id="header">
    <div class="container header__inner">
      <a href="#" class="logo">
        <span class="logo__dot"></span>
        <span class="logo__text">Hanul Beauty</span>
      </a>
      <nav class="nav">
        <ul class="nav__list">
          <li><a href="#tratamientos" class="nav__link">Tratamientos</a></li>
          <li><a href="#galeria" class="nav__link">Galeria</a></li>
          <li><a href="#nosotros" class="nav__link">Sobre Nosotros</a></li>
          <li><a href="#ubicacion" class="nav__link">Ubicacion</a></li>
        </ul>
      </nav>
      <div class="header__actions">
        <a href="#agendar" class="btn btn--primary btn--sm">Agendar Cita</a>
        <div class="user-menu" id="userMenu">
          <button class="user-menu__toggle" aria-label="Menu de usuario" id="userMenuToggle">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </button>
          <div class="user-menu__dropdown" id="userMenuDropdown">
            <!-- Estado no logueado -->
            <div class="user-menu__guest" id="userMenuGuest" <?php if (is_logged_in()): ?>style="display:none"<?php endif; ?>>
              <button class="user-menu__item" data-open-modal="loginModal">Iniciar sesion</button>
              <button class="user-menu__item" data-open-modal="registerModal">Registrarse</button>
            </div>
            <!-- Estado logueado -->
            <div class="user-menu__logged" id="userMenuLogged" <?php if (!is_logged_in()): ?>style="display:none"<?php endif; ?>>
              <?php if (is_logged_in() && $currentUser): ?>
                <span class="user-menu__name" style="padding: 8px 16px; font-weight: 500; color: var(--color-text);"><?php echo sanitize($currentUser['nombre']); ?></span>
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
      </div>
      <button class="nav__toggle" aria-label="Abrir menu">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </header>
