<?php
require_once __DIR__ . '/includes/auth.php';
start_session();
$currentUser = current_user();

$pageTitle       = 'Galeria | Hanul Beauty';
$pageDescription = 'Conoce los rituales, texturas y espacios de Hanul Beauty.';
$bodyClass       = 'gallery-page';
$activeNav       = 'galeria';
$isHome          = false;

require __DIR__ . '/templates/header.php';
?>

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
