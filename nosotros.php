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
  <meta name="description" content="Conoce la historia, filosofia y equipo de Hanul Beauty.">
  <title>Sobre Nosotros | Hanul Beauty</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>">
</head>
<body class="about-page">
  <header class="header">
    <div class="container header__inner">
      <a href="index.php" class="logo">
        <span class="logo__dot"></span>
        <span class="logo__text">Hanul Beauty</span>
      </a>
      <nav class="nav" aria-label="Navegacion principal">
        <ul class="nav__list">
          <li><a href="index.php#tratamientos" class="nav__link">Tratamientos</a></li>
          <li><a href="galeria.php" class="nav__link">Galeria</a></li>
          <li><a href="nosotros.php" class="nav__link nav__link--active" aria-current="page">Sobre Nosotros</a></li>
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
    <section class="about-hero">
      <div class="container about-hero__grid">
        <div class="about-hero__content">
          <p class="section__label">NUESTRA ESENCIA</p>
          <h1>Cuidar la piel también es aprender a <em>escucharla</em></h1>
          <p>
            Hanul nació para acercar la filosofía del cuidado coreano a una rutina
            honesta, pausada y posible. Aquí cada piel recibe tiempo, criterio y un
            ritual diseñado alrededor de lo que realmente necesita.
          </p>
          <a href="index.php#tratamientos" class="btn btn--primary">Conocer tratamientos</a>
        </div>
        <div class="about-hero__visual">
          <img src="assets/images/estudio-hanul.jpg" alt="Interior del estudio Hanul Beauty" width="1536" height="1024">
        </div>
      </div>
    </section>

    <section class="about-story">
      <div class="container about-story__grid">
        <div class="about-story__image">
          <img src="assets/images/glass-skin.jpg" alt="Formulas de hidratacion utilizadas en Hanul Beauty" width="1456" height="1092" loading="lazy">
        </div>
        <div class="about-story__content">
          <p class="section__label">DESDE 2024</p>
          <h2 class="section__title">Menos promesas rápidas.<br>Más cuidado constante.</h2>
          <p>
            Comenzamos imaginando un estudio donde una cita facial no se sintiera
            apresurada ni estandarizada. Nuestra inspiración viene de los rituales
            coreanos por capas: observar, preparar, tratar e hidratar con intención.
          </p>
          <p>
            Combinamos esa mirada con valoración profesional y productos elegidos
            según el estado actual de cada piel. El objetivo no es perseguir la
            perfección, sino construir bienestar y resultados sostenibles.
          </p>
        </div>
      </div>
    </section>

    <section class="about-values">
      <div class="container">
        <div class="about-values__heading">
          <p class="section__label">LO QUE NOS GUIA</p>
          <h2 class="section__title">Una forma más consciente de cuidar</h2>
        </div>
        <div class="about-values__grid">
          <article class="about-value">
            <span>01</span>
            <h3>Escucha antes de tratar</h3>
            <p>Cada visita comienza entendiendo tu piel, tus hábitos y aquello que quieres mejorar.</p>
          </article>
          <article class="about-value">
            <span>02</span>
            <h3>Suavidad con propósito</h3>
            <p>Preferimos protocolos progresivos que respetan la barrera natural y evitan excesos.</p>
          </article>
          <article class="about-value">
            <span>03</span>
            <h3>Resultados sostenibles</h3>
            <p>Diseñamos recomendaciones realistas para acompañar tu piel dentro y fuera del estudio.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="about-team">
      <div class="container about-team__grid">
        <div>
          <p class="section__label">NUESTRO EQUIPO</p>
          <h2 class="section__title">Manos expertas,<br>atencion cercana</h2>
          <p class="section__description">
            Nuestro equipo reúne experiencia estética, formación continua y una
            misma convicción: ninguna piel debería sentirse como un proceso en serie.
          </p>
          <a href="ubicacion.php" class="btn btn--outline">Visitar el estudio</a>
        </div>
        <div class="about-team__mosaic">
          <img src="assets/images/limpieza-profunda.jpg" alt="Preparacion para una limpieza facial" width="1456" height="1092" loading="lazy">
          <img src="assets/images/hidratacion-coreana.jpg" alt="Mascarilla para hidratacion profunda" width="1456" height="1092" loading="lazy">
        </div>
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
