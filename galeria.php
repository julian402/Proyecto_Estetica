<?php
require_once __DIR__ . '/includes/auth.php';
start_session();
$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Conoce los rituales, texturas y espacios de Hanul Beauty.">
  <title>Galeria | Hanul Beauty</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>">
</head>
<body class="gallery-page">
  <header class="header">
    <div class="container header__inner">
      <a href="index.php" class="logo">
        <span class="logo__dot"></span>
        <span class="logo__text">Hanul Beauty</span>
      </a>
      <nav class="nav" aria-label="Navegacion principal">
        <ul class="nav__list">
          <li><a href="index.php#tratamientos" class="nav__link">Tratamientos</a></li>
          <li><a href="galeria.php" class="nav__link nav__link--active" aria-current="page">Galeria</a></li>
          <li><a href="nosotros.php" class="nav__link">Sobre Nosotros</a></li>
          <li><a href="ubicacion.php" class="nav__link">Ubicacion</a></li>
        </ul>
      </nav>
      <div class="header__actions">
        <a href="index.php#agendar" class="btn btn--primary btn--sm">Agendar cita</a>
        <?php require __DIR__ . '/templates/user-menu.php'; ?>
      </div>
    </div>
  </header>

  <main>
    <section class="gallery-intro">
      <div class="container gallery-intro__inner">
        <p class="section__label">HANUL BEAUTY</p>
        <h1>Rituales que se sienten<br><em>tan bien como se ven</em></h1>
        <p>Una mirada a nuestras formulas, texturas y al espacio donde hacemos del cuidado una pausa cotidiana.</p>
      </div>
    </section>

    <?php require __DIR__ . '/templates/gallery.php'; ?>

    <section class="gallery-banner">
      <div class="container gallery-banner__inner">
        <div>
          <p class="section__label">TU MOMENTO</p>
          <h2 class="section__title">Descubre el ritual ideal para tu piel</h2>
        </div>
        <a href="index.php#agendar" class="btn btn--primary">Reservar una cita</a>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container footer__bottom location-footer">
      <a href="index.php" class="logo"><span class="logo__dot"></span><span class="logo__text">Hanul Beauty</span></a>
      <p>&copy; 2026 Hanul Beauty · Bogota, Colombia</p>
    </div>
  </footer>
  <?php require __DIR__ . '/templates/modals.php'; ?>
  <script src="js/app.js?v=<?php echo filemtime(__DIR__ . '/js/app.js'); ?>"></script>
</body>
</html>
