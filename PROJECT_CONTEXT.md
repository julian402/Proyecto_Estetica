# Contexto del proyecto: Hanul Beauty

## Propósito

Aplicación web para un centro de estética coreana en Bogotá. Permite a clientes reservar tratamientos y al personal administrar agenda, servicios, empleados, bloqueos, contingencias, correos e informes.

## Entorno local

- Ruta: `C:\xampp\htdocs\Proyecto_Estetica`
- URL: `http://localhost/Proyecto_Estetica/`
- Servidor: XAMPP con Apache y MySQL.
- PHP: 8.2 sin framework.
- Base de datos: MySQL/MariaDB, esquema `kbeauty_db`.
- Zona horaria: `America/Bogota` en `config/database.php` mediante `APP_TIMEZONE`.

## Arquitectura

- `index.php`: sitio público y reserva.
- `cuenta.php`: área privada del cliente.
- `dashboard.php`: panel de SuperAdmin, Recepción y Esteticistas.
- `api/`: endpoints JSON organizados por autenticación, reservas y administración.
- `models/`: acceso a datos (`User`, `Treatment`, `Appointment`, `Favorite`).
- `includes/`: conexión, sesiones, CSRF, auditoría y correo.
- `sql/schema.sql`: estructura y datos semilla.
- `assets/images/`: imágenes públicas del sitio.

## Sesiones y roles

- 1: Cliente
- 2: SuperAdmin
- 3: Recepcionista
- 4: Esteticista

El cliente se dirige a `cuenta.php`; los roles 2, 3 y 4 usan `dashboard.php`.
Al cerrar sesión, cualquier usuario vuelve a `index.php`.

## Servicios e imágenes

La tabla `servicios` contiene `imagen_url` opcional. Las imágenes se sirven desde `assets/images/services/`.

- El panel de tratamientos permite cargar JPG, PNG o WebP de hasta 5 MB al crear o editar.
- Si no hay archivo al crear, se usa `assets/images/glass-skin.jpg` como imagen predeterminada.
- Si no hay archivo al editar, se mantiene la imagen existente.
- Las cinco imágenes recientes se guardan en `assets/images/services/`.

## Cambios recientes

- `setup.php` guarda la zona horaria y refleja el esquema actual.
- El avatar del usuario se actualiza inmediatamente al iniciar sesión.
- El cierre de sesión redirige al inicio público.
- Se unificaron los radios de bordes del dashboard y de sus tablas.
- Se añadieron imágenes opcionales por servicio y cinco recursos visuales para el catálogo reciente.

## Reglas de trabajo

- Conservar las credenciales fuera de Git: `config/database.php` y `config/mail.php`.
- Ejecutar `C:\xampp\php\php.exe -l <archivo.php>` para validar PHP.
- Ejecutar `node --check <archivo.js>` para validar JavaScript.
- Las ramas `main`, `develop` y `develop-24` deben mantenerse sincronizadas cuando se publiquen cambios.
