<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hanul Beauty - Cuidado Facial Coreano en Bogota</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/../css/styles.css'); ?>">
</head>
<body>

  <!-- ========== HEADER / NAV ========== -->
  <header class="header" id="header">
    <div class="container header__inner">
      <a href="index.php" class="logo">
        <span class="logo__dot"></span>
        <span class="logo__text">Hanul Beauty</span>
      </a>
      <nav class="nav">
        <ul class="nav__list">
          <li><a href="#tratamientos" class="nav__link">Tratamientos</a></li>
          <li><a href="galeria.php" class="nav__link">Galeria</a></li>
          <li><a href="nosotros.php" class="nav__link">Sobre Nosotros</a></li>
          <li><a href="ubicacion.php" class="nav__link">Ubicacion</a></li>
        </ul>
      </nav>
      <div class="header__actions">
        <a href="#agendar" class="btn btn--primary btn--sm">Agendar Cita</a>
        <?php require __DIR__ . '/user-menu.php'; ?>
      </div>
      <button class="nav__toggle" aria-label="Abrir menu">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </header>
