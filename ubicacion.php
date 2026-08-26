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
  <meta name="description" content="Ubicacion, horarios y recomendaciones para visitar Hanul Beauty en Bogota.">
  <title>Visitanos | Hanul Beauty</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>">
</head>
<body class="location-page">
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
          <li><a href="nosotros.php" class="nav__link">Sobre Nosotros</a></li>
          <li><a href="ubicacion.php" class="nav__link nav__link--active" aria-current="page">Ubicacion</a></li>
        </ul>
      </nav>
      <div class="header__actions">
        <a href="index.php#agendar" class="btn btn--primary btn--sm">Agendar cita</a>
        <?php require __DIR__ . '/templates/user-menu.php'; ?>
      </div>
    </div>
  </header>

  <main>
    <section class="location-hero">
      <img src="assets/images/estudio-hanul.jpg" alt="Interior sereno del estudio Hanul Beauty" width="1536" height="1024">
      <div class="location-hero__overlay"></div>
      <div class="container location-hero__content">
        <p class="section__label">NUESTRO ESTUDIO</p>
        <h1>Un refugio de calma<br>en el norte de Bogota</h1>
        <p>Un espacio privado, luminoso y diseñado para cuidar tu piel sin prisa.</p>
      </div>
    </section>

    <section class="location-info">
      <div class="container location-info__grid">
        <div class="location-info__intro">
          <p class="section__label">VISITANOS</p>
          <h2 class="section__title">Todo listo para recibirte</h2>
          <p class="section__description">
            Esta informacion es demostrativa y se puede reemplazar por la direccion
            y los datos definitivos del estudio.
          </p>
        </div>
        <div class="location-details">
          <article class="location-card">
            <span class="location-card__number">01</span>
            <h3>Direccion</h3>
            <p>Calle 93 # 14–32, local 204<br>Chico, Bogota</p>
            <a href="https://www.google.com/maps/search/?api=1&query=Calle+93+Bogota" target="_blank" rel="noopener noreferrer">Abrir en Google Maps &rarr;</a>
          </article>
          <article class="location-card">
            <span class="location-card__number">02</span>
            <h3>Horarios</h3>
            <p>Lunes a viernes: 9:00 a. m. – 7:00 p. m.<br>Sabados: 9:00 a. m. – 5:00 p. m.</p>
            <span>Atendemos con cita previa</span>
          </article>
          <article class="location-card">
            <span class="location-card__number">03</span>
            <h3>Contacto</h3>
            <p>+57 300 555 0147<br>hola@hanulbeauty.co</p>
            <a href="mailto:hola@hanulbeauty.co">Escribenos &rarr;</a>
          </article>
        </div>
      </div>
    </section>

    <section class="arrival">
      <div class="container arrival__grid">
        <div class="arrival__visual">
          <img src="assets/images/drenaje-linfatico.jpg" alt="Detalles de bienestar del estudio" width="1456" height="1092" loading="lazy">
        </div>
        <div class="arrival__content">
          <p class="section__label">ANTES DE LLEGAR</p>
          <h2 class="section__title">Tu visita, sin complicaciones</h2>
          <div class="arrival__item">
            <h3>En carro</h3>
            <p>Parqueadero público en el edificio y opciones cercanas sobre la Calle 93.</p>
          </div>
          <div class="arrival__item">
            <h3>Transporte público</h3>
            <p>A diez minutos caminando de la estacion Virrey y cerca de rutas por la Carrera 15.</p>
          </div>
          <div class="arrival__item">
            <h3>Tu primera cita</h3>
            <p>Llega diez minutos antes para realizar una breve valoracion de piel.</p>
          </div>
          <a href="index.php#agendar" class="btn btn--primary">Reservar mi visita</a>
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
