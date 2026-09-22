<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/User.php';

start_session();
$email = strtolower(trim($_GET['email'] ?? ''));
$user = filter_var($email, FILTER_VALIDATE_EMAIL) ? User::findByEmailAny($email) : null;
if (!$user || empty($user['es_invitado'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Completa tu cuenta - Hanul Beauty</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>">
</head>
<body class="complete-profile-page">
  <main class="complete-profile">
    <a class="logo complete-profile__logo" href="index.php"><span class="logo__dot"></span><span class="logo__text">Hanul Beauty</span></a>
    <section class="complete-profile__card" aria-labelledby="completeTitle">
      <p class="section__label">TU CUENTA</p>
      <h1 class="section__title" id="completeTitle">Completa tu perfil</h1>
      <p class="complete-profile__intro">Tus datos de reserva ya están listos. Crea una contraseña para consultar y gestionar tus citas.</p>
      <form id="completeProfileForm" class="modal__form" novalidate>
        <input type="hidden" id="completeCsrf" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" id="completeEmail" value="<?php echo htmlspecialchars($user['correo'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group"><label class="form-label">Nombre completo</label><input class="form-input" value="<?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
        <div class="form-group"><label class="form-label">Correo electrónico</label><input class="form-input" value="<?php echo htmlspecialchars($user['correo'], ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
        <div class="form-group"><label class="form-label">Teléfono</label><input class="form-input" value="<?php echo htmlspecialchars($user['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
        <div class="form-group"><label class="form-label" for="completePassword">Contraseña</label><input class="form-input" id="completePassword" type="password" minlength="8" maxlength="128" autocomplete="new-password" required></div>
        <div class="form-group"><label class="form-label" for="completePasswordConfirm">Confirmar contraseña</label><input class="form-input" id="completePasswordConfirm" type="password" minlength="8" maxlength="128" autocomplete="new-password" required></div>
        <p class="modal__error" id="completeProfileError" hidden></p>
        <button class="btn btn--primary btn--full" type="submit">Activar mi cuenta</button>
      </form>
      <p class="complete-profile__login">¿Ya tienes una cuenta? <a href="index.php">Inicia sesión</a></p>
    </section>
  </main>
  <script src="js/complete-profile.js?v=<?php echo filemtime(__DIR__ . '/js/complete-profile.js'); ?>"></script>
</body>
</html>
