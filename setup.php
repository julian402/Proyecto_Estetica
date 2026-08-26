<?php
/**
 * Hanul Beauty - Instalador de base de datos
 *
 * Este script:
 *  1. Verifica/crea config/database.php
 *  2. Conecta a MySQL
 *  3. Ejecuta sql/schema.sql (tablas, triggers, datos semilla)
 *
 * Uso: abrir http://localhost/Proyecto_Estetica/setup.php en el navegador
 */

// ---- Rutas ----
$configFile   = __DIR__ . '/config/database.php';
$exampleFile  = __DIR__ . '/config/database.php.example';
$schemaFile   = __DIR__ . '/sql/schema.sql';

// ---- Estado ----
$step           = 'config';   // config | install | done
$message        = '';
$messageType    = '';         // success | error | warning
$results        = [];
$configExists   = file_exists($configFile);
$dbExists       = false;
$dbConnected    = false;

// ============================================================
// ACCION: Crear config/database.php
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'config') {
    $host    = trim($_POST['host'] ?? 'localhost');
    $dbname  = trim($_POST['dbname'] ?? 'kbeauty_db');
    $user    = trim($_POST['user'] ?? 'root');
    $pass    = $_POST['pass'] ?? '';
    $charset = 'utf8mb4';

    $content = "<?php\n";
    $content .= "// Credenciales de base de datos - NO subir a git\n";
    $content .= "define('DB_HOST', " . var_export($host, true) . ");\n";
    $content .= "define('DB_NAME', " . var_export($dbname, true) . ");\n";
    $content .= "define('DB_USER', " . var_export($user, true) . ");\n";
    $content .= "define('DB_PASS', " . var_export($pass, true) . ");\n";
    $content .= "define('DB_CHARSET', " . var_export($charset, true) . ");\n";

    if (@file_put_contents($configFile, $content)) {
        $configExists = true;
        $message     = 'Archivo de configuracion creado correctamente.';
        $messageType = 'success';
    } else {
        $message     = 'No se pudo escribir config/database.php. Verifica los permisos de la carpeta config/ o crea el archivo manualmente copiando config/database.php.example';
        $messageType = 'error';
    }
}

// ============================================================
// Si config existe, intentar conexion a MySQL
// ============================================================
if ($configExists) {
    require_once $configFile;
    $step = 'install';

    try {
        // Conexion SIN base de datos (para poder crearla)
        $dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $dbConnected = true;

        // Verificar si la base de datos ya existe
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote(DB_NAME));
        $dbExists = (bool) $stmt->fetch();

    } catch (PDOException $e) {
        $message     = 'No se pudo conectar a MySQL: ' . $e->getMessage() . '. Verifica que XAMPP este encendido (Apache + MySQL).';
        $messageType = 'error';
        $step        = 'config';
    }
}

// ============================================================
// ACCION: Instalar base de datos
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'install' && $dbConnected) {

    if (!file_exists($schemaFile)) {
        $message     = 'No se encontro el archivo sql/schema.sql';
        $messageType = 'error';
    } else {
        $results = executeSqlFile($pdo, $schemaFile);

        $errors = array_filter($results, function ($r) { return !$r['success']; });

        if (empty($errors)) {
            $step        = 'done';
            $message     = 'Base de datos instalada correctamente. Se ejecutaron ' . count($results) . ' instrucciones.';
            $messageType = 'success';
        } else {
            $message     = 'La instalacion termino con ' . count($errors) . ' error(es). Revisa los detalles abajo.';
            $messageType = 'warning';
        }
    }
}

// ============================================================
// Parser de SQL con soporte para DELIMITER (triggers)
// ============================================================
function executeSqlFile(PDO $pdo, string $filePath): array {
    $content   = file_get_contents($filePath);
    $lines     = explode("\n", $content);
    $delimiter = ';';
    $statement = '';
    $results   = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Saltar lineas vacias y comentarios puros
        if ($trimmed === '' || strpos($trimmed, '--') === 0) {
            // Pero si estamos acumulando un statement, mantener el salto
            if ($statement !== '') {
                $statement .= "\n";
            }
            continue;
        }

        // Detectar cambio de DELIMITER
        if (preg_match('/^DELIMITER\s+(\S+)\s*$/i', $trimmed, $matches)) {
            $delimiter = $matches[1];
            continue;
        }

        $statement .= $line . "\n";

        // Verificar si el statement acumulado termina con el delimiter actual
        $trimmedStmt = rtrim($statement);
        if (strlen($trimmedStmt) >= strlen($delimiter)
            && substr($trimmedStmt, -strlen($delimiter)) === $delimiter) {

            // Quitar el delimiter del final
            $query = trim(substr($trimmedStmt, 0, -strlen($delimiter)));

            if ($query !== '') {
                // Descripcion corta para mostrar en la UI
                $preview = mb_substr($query, 0, 100);
                $preview = preg_replace('/\s+/', ' ', $preview);

                try {
                    $pdo->exec($query);
                    $results[] = ['success' => true, 'preview' => $preview];
                } catch (PDOException $e) {
                    $results[] = ['success' => false, 'preview' => $preview, 'error' => $e->getMessage()];
                }
            }

            $statement = '';
        }
    }

    return $results;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hanul Beauty - Instalacion</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', sans-serif;
      background: #faf9f7;
      color: #2d2a26;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    .setup {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
      max-width: 600px;
      width: 100%;
      padding: 2.5rem;
    }
    .setup__logo {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 0.25rem;
    }
    .setup__logo::before {
      content: '';
      display: inline-block;
      width: 8px; height: 8px;
      border-radius: 50%;
      background: #c9a87c;
      margin-right: 8px;
      vertical-align: middle;
    }
    .setup__subtitle {
      font-size: 0.85rem;
      color: #888;
      margin-bottom: 2rem;
    }
    h2 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 1rem;
    }
    .steps {
      display: flex;
      gap: 0.5rem;
      margin-bottom: 2rem;
    }
    .step-indicator {
      flex: 1;
      height: 4px;
      border-radius: 2px;
      background: #e8e4df;
    }
    .step-indicator--active { background: #c9a87c; }
    .step-indicator--done { background: #27ae60; }
    .form-group { margin-bottom: 1rem; }
    .form-label {
      display: block;
      font-size: 0.8rem;
      font-weight: 500;
      color: #555;
      margin-bottom: 0.35rem;
    }
    .form-input {
      width: 100%;
      padding: 0.6rem 0.8rem;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-size: 0.9rem;
      font-family: 'Inter', monospace;
      transition: border-color 0.2s;
    }
    .form-input:focus {
      outline: none;
      border-color: #c9a87c;
    }
    .form-hint {
      font-size: 0.75rem;
      color: #999;
      margin-top: 0.25rem;
    }
    .btn {
      display: inline-block;
      padding: 0.7rem 1.5rem;
      border: none;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 500;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s;
    }
    .btn--primary {
      background: #c9a87c;
      color: #fff;
      width: 100%;
    }
    .btn--primary:hover { background: #b8955e; }
    .btn--link {
      background: none;
      color: #c9a87c;
      padding: 0.7rem 0;
    }
    .msg {
      padding: 0.8rem 1rem;
      border-radius: 8px;
      font-size: 0.85rem;
      margin-bottom: 1.5rem;
      line-height: 1.5;
    }
    .msg--success { background: #eafaf1; color: #1e7e46; border: 1px solid #b7e4c7; }
    .msg--error   { background: #fdecea; color: #c0392b; border: 1px solid #f5c6cb; }
    .msg--warning { background: #fef9e7; color: #856404; border: 1px solid #fceabb; }
    .msg--info    { background: #eaf0fa; color: #2c5282; border: 1px solid #bee3f8; }
    .results {
      max-height: 300px;
      overflow-y: auto;
      border: 1px solid #e8e4df;
      border-radius: 8px;
      margin-top: 1rem;
      font-size: 0.78rem;
    }
    .result-row {
      padding: 0.5rem 0.8rem;
      border-bottom: 1px solid #f0eeeb;
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
    }
    .result-row:last-child { border-bottom: none; }
    .result-row--ok .result-icon { color: #27ae60; }
    .result-row--err .result-icon { color: #e74c3c; }
    .result-icon { flex-shrink: 0; font-weight: bold; }
    .result-text {
      font-family: monospace;
      word-break: break-all;
      color: #555;
    }
    .result-error { color: #c0392b; font-size: 0.75rem; margin-top: 0.2rem; }
    .db-warning {
      background: #fef9e7;
      border: 1px solid #fceabb;
      border-radius: 8px;
      padding: 0.8rem 1rem;
      margin-bottom: 1rem;
      font-size: 0.85rem;
      color: #856404;
    }
  </style>
</head>
<body>
  <div class="setup">
    <div class="setup__logo">Hanul Beauty</div>
    <p class="setup__subtitle">Asistente de instalacion</p>

    <!-- Indicador de pasos -->
    <div class="steps">
      <div class="step-indicator <?php
        echo $step === 'config' ? 'step-indicator--active' : 'step-indicator--done';
      ?>"></div>
      <div class="step-indicator <?php
        echo $step === 'install' ? 'step-indicator--active' : ($step === 'done' ? 'step-indicator--done' : '');
      ?>"></div>
      <div class="step-indicator <?php
        echo $step === 'done' ? 'step-indicator--done' : '';
      ?>"></div>
    </div>

    <!-- Mensajes -->
    <?php if ($message): ?>
      <div class="msg msg--<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <!-- ======== PASO 1: CONFIGURACION ======== -->
    <?php if ($step === 'config'): ?>
      <h2>Paso 1 — Configuracion de base de datos</h2>
      <p style="font-size: 0.85rem; color: #666; margin-bottom: 1.5rem;">
        Ingresa las credenciales de tu servidor MySQL. Si usas XAMPP con la configuracion
        por defecto, solo presiona el boton.
      </p>

      <form method="post">
        <input type="hidden" name="action" value="config">
        <div class="form-group">
          <label class="form-label" for="host">Host</label>
          <input type="text" class="form-input" id="host" name="host" value="localhost">
        </div>
        <div class="form-group">
          <label class="form-label" for="dbname">Nombre de la base de datos</label>
          <input type="text" class="form-input" id="dbname" name="dbname" value="kbeauty_db" readonly>
          <p class="form-hint">Definido en schema.sql — no se puede cambiar desde aqui.</p>
        </div>
        <div class="form-group">
          <label class="form-label" for="user">Usuario</label>
          <input type="text" class="form-input" id="user" name="user" value="root">
        </div>
        <div class="form-group">
          <label class="form-label" for="pass">Contrasena</label>
          <input type="password" class="form-input" id="pass" name="pass" value="" placeholder="Vacio por defecto en XAMPP">
        </div>
        <button type="submit" class="btn btn--primary">Guardar configuracion</button>
      </form>

    <!-- ======== PASO 2: INSTALAR DB ======== -->
    <?php elseif ($step === 'install'): ?>
      <h2>Paso 2 — Instalar base de datos</h2>

      <?php if ($dbExists): ?>
        <div class="db-warning">
          La base de datos <strong><?php echo htmlspecialchars(DB_NAME); ?></strong> ya existe.
          Al reinstalar se eliminaran todos los datos actuales y se creara desde cero.
        </div>
      <?php endif; ?>

      <p style="font-size: 0.85rem; color: #666; margin-bottom: 0.5rem;">
        Conexion a <strong><?php echo htmlspecialchars(DB_HOST); ?></strong> como
        <strong><?php echo htmlspecialchars(DB_USER); ?></strong> — OK
      </p>
      <p style="font-size: 0.85rem; color: #666; margin-bottom: 1.5rem;">
        Se ejecutara <code>sql/schema.sql</code> que crea:
      </p>
      <ul style="font-size: 0.85rem; color: #666; margin-bottom: 1.5rem; padding-left: 1.2rem;">
        <li>10 tablas (usuarios, servicios, reservas, etc.)</li>
        <li>4 triggers de validacion de horarios</li>
        <li>1 vista de agenda</li>
        <li>Datos semilla (roles, estados, servicios, esteticistas)</li>
      </ul>

      <form method="post">
        <input type="hidden" name="action" value="install">
        <button type="submit" class="btn btn--primary"
                onclick="return <?php echo $dbExists ? "confirm('Esto eliminara la base de datos existente. Continuar?')" : 'true'; ?>">
          <?php echo $dbExists ? 'Reinstalar base de datos' : 'Instalar base de datos'; ?>
        </button>
      </form>

      <!-- Resultados de ejecucion si los hay -->
      <?php if (!empty($results)): ?>
        <div class="results">
          <?php foreach ($results as $r): ?>
            <div class="result-row <?php echo $r['success'] ? 'result-row--ok' : 'result-row--err'; ?>">
              <span class="result-icon"><?php echo $r['success'] ? '&#10003;' : '&#10007;'; ?></span>
              <div>
                <div class="result-text"><?php echo htmlspecialchars($r['preview']); ?></div>
                <?php if (!$r['success']): ?>
                  <div class="result-error"><?php echo htmlspecialchars($r['error']); ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" style="margin-top: 1rem;">
        <input type="hidden" name="action" value="config">
        <input type="hidden" name="host" value="<?php echo htmlspecialchars(DB_HOST); ?>">
        <input type="hidden" name="dbname" value="<?php echo htmlspecialchars(DB_NAME); ?>">
        <input type="hidden" name="user" value="<?php echo htmlspecialchars(DB_USER); ?>">
        <input type="hidden" name="pass" value="">
        <button type="button" class="btn btn--link" onclick="history.back()">
          &larr; Cambiar configuracion
        </button>
      </form>

    <!-- ======== PASO 3: LISTO ======== -->
    <?php elseif ($step === 'done'): ?>
      <h2>Instalacion completada</h2>

      <p style="font-size: 0.85rem; color: #666; margin-bottom: 1.5rem;">
        La base de datos <strong><?php echo htmlspecialchars(DB_NAME); ?></strong> esta lista.
        Ya puedes usar el sitio.
      </p>

      <!-- Resultados de ejecucion -->
      <?php if (!empty($results)): ?>
        <div class="results" style="margin-bottom: 1.5rem;">
          <?php foreach ($results as $r): ?>
            <div class="result-row <?php echo $r['success'] ? 'result-row--ok' : 'result-row--err'; ?>">
              <span class="result-icon"><?php echo $r['success'] ? '&#10003;' : '&#10007;'; ?></span>
              <div>
                <div class="result-text"><?php echo htmlspecialchars($r['preview']); ?></div>
                <?php if (!$r['success']): ?>
                  <div class="result-error"><?php echo htmlspecialchars($r['error']); ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <a href="index.php" class="btn btn--primary" style="display: block; text-align: center;">
        Ir al sitio
      </a>
    <?php endif; ?>
  </div>
</body>
</html>
