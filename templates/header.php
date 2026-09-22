<?php
/**
 * Cabecera compartida por index.php, galeria.php, nosotros.php y ubicacion.php.
 *
 * Variables opcionales que la pagina puede definir antes del require:
 *   $pageTitle       Titulo del documento
 *   $pageDescription Meta description
 *   $bodyClass       Clase del <body>
 *   $activeNav       Enlace activo: tratamientos | galeria | nosotros | ubicacion
 *   $isHome          true en index.php (los anclas son locales)
 */
$pageTitle       = $pageTitle       ?? 'Hanul Beauty - Cuidado Facial Coreano en Bogota';
$pageDescription = $pageDescription ?? 'Estudio de cuidado facial coreano en Bogota. Agenda tu ritual K-Beauty en Hanul Beauty.';
$bodyClass       = $bodyClass       ?? '';
$activeNav       = $activeNav       ?? '';
$isHome          = $isHome          ?? true;

$homePrefix = $isHome ? '' : 'index.php';
$bookHref   = $homePrefix . '#agendar';

$navLinks = [
    'tratamientos' => [$homePrefix . '#tratamientos', 'Tratamientos'],
    'galeria'      => ['galeria.php',                 'Galeria'],
    'nosotros'     => ['nosotros.php',                'Sobre Nosotros'],
    'ubicacion'    => ['ubicacion.php',               'Ubicacion'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?php echo sanitize($pageDescription); ?>">
  <title><?php echo sanitize($pageTitle); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/../css/styles.css'); ?>">
</head>
<body<?php echo $bodyClass ? ' class="' . sanitize($bodyClass) . '"' : ''; ?>>
  <header class="header" id="header">
    <div class="container header__inner">
      <a href="index.php" class="logo">
        <span class="logo__dot"></span>
        <span class="logo__text">Hanul Beauty</span>
      </a>
      <nav class="nav" aria-label="Navegacion principal">
        <ul class="nav__list">
          <?php foreach ($navLinks as $key => $link): ?>
            <li>
              <a href="<?php echo $link[0]; ?>"
                 class="nav__link<?php echo $activeNav === $key ? ' nav__link--active' : ''; ?>"
                 <?php echo $activeNav === $key ? 'aria-current="page"' : ''; ?>>
                <?php echo $link[1]; ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <div class="header__actions">
        <a href="<?php echo $bookHref; ?>" class="btn btn--primary btn--sm">Agendar cita</a>
        <?php require __DIR__ . '/user-menu.php'; ?>
      </div>
      <button class="nav__toggle" id="navToggle" type="button"
              aria-label="Abrir menu de navegacion" aria-controls="mobileNav" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
    <div class="mobile-nav" id="mobileNav">
      <ul class="mobile-nav__list">
        <?php foreach ($navLinks as $key => $link): ?>
          <li>
            <a href="<?php echo $link[0]; ?>"
               class="mobile-nav__link<?php echo $activeNav === $key ? ' mobile-nav__link--active' : ''; ?>">
              <?php echo $link[1]; ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
      <a href="<?php echo $bookHref; ?>" class="btn btn--primary btn--full mobile-nav__cta">Agendar cita</a>
    </div>
  </header>
