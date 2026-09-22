# Hanul Beauty

Sistema web de gestion para centro de estetica coreana en Bogota. Permite a los clientes agendar citas online y al equipo administrativo gestionar reservas desde un dashboard.

## Tecnologias

- **Backend:** PHP 8.2 (sin framework)
- **Base de datos:** MySQL / MariaDB
- **Servidor:** Apache (XAMPP)
- **Frontend:** HTML5, CSS3, JavaScript vanilla

## Requisitos

- [XAMPP](https://www.apachefriends.org/) con Apache + MySQL activos
- PHP 8.0 o superior

## Instalacion

1. Clonar el repositorio en `htdocs`:
   ```bash
   cd /opt/lampp/htdocs
   git clone https://github.com/julian402/Proyecto_Estetica.git
   ```

2. Copiar la configuracion de base de datos:
   ```bash
   cp config/database.php.example config/database.php
   ```

3. Editar `config/database.php` con tus credenciales (por defecto: root sin password).

4. Iniciar XAMPP (Apache + MySQL).

5. Abrir en el navegador:
   ```
   http://localhost/Proyecto_Estetica/
   ```

6. El sistema detectara que la base de datos no existe y mostrara el **wizard de instalacion** automaticamente. Seguir los 3 pasos para crear las tablas, triggers y datos iniciales.


## Correo transaccional

El sistema envia notificaciones por correo al agendar, confirmar, completar,
cancelar o reprogramar una cita, al aplicar un plan de contingencia y al crear
una cuenta. Cada correo queda registrado en `logs/emails.log` y en la tabla
`correos_log`, consultable desde el panel en **Correos**.

### Modo por defecto: registro sin envio

Sin configuracion adicional el sistema trabaja en modo `log`: genera el correo
y lo registra, pero no lo entrega. Es lo practico en XAMPP, donde `mail()` no
funciona sin un sendmail configurado. Para revisar lo que se habria enviado:

- Panel de administracion → boton **Correos** (incluye vista previa del HTML)
- Archivo `logs/emails.log`
- Tabla `correos_log`

### Envio real por SMTP

1. Copiar la plantilla de configuracion:
   ```bash
   cp config/mail.php.example config/mail.php
   ```

2. En `config/mail.php`, poner `MAIL_TRANSPORT` en `'smtp'` y completar el resto.

**Mailtrap** (recomendado para pruebas: captura los correos en una bandeja de
prueba, sin entregarlos a buzones reales):

```php
define('MAIL_TRANSPORT', 'smtp');
define('MAIL_HOST', 'sandbox.smtp.mailtrap.io');
define('MAIL_PORT', 2525);
define('MAIL_SECURE', 'tls');
define('MAIL_USER', 'tu-usuario-de-mailtrap');
define('MAIL_PASS', 'tu-password-de-mailtrap');
```

**Gmail** (entrega real). Requiere verificacion en dos pasos activa y una
[contrasena de aplicacion](https://myaccount.google.com/apppasswords); la
contrasena normal de la cuenta **no** funciona:

```php
define('MAIL_TRANSPORT', 'smtp');
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_SECURE', 'tls');
define('MAIL_USER', 'tucorreo@gmail.com');
define('MAIL_PASS', 'la-contrasena-de-aplicacion');
define('MAIL_FROM', 'tucorreo@gmail.com');
```

> `config/mail.php` esta en `.gitignore`: nunca se sube al repositorio.
> Si el envio falla, la operacion de negocio (la reserva, la cancelacion) se
> completa igual y `correos_log.estado` guarda el motivo del fallo.

El envio usa `includes/smtp.php`, un cliente SMTP propio sobre sockets: el
proyecto no necesita Composer ni dependencias externas.

## Estructura del proyecto

```
Proyecto_Estetica/
├── api/                    # Endpoints REST (JSON)
│   ├── auth/               # login, register, logout, profile
│   ├── appointments/       # create, list, cancel, all, update-status, availability, reschedule
│   ├── admin/              # services, employees, contingency, report, logs, emails
│   └── favorites/          # list, toggle
├── config/                 # Credenciales DB y correo (excluidos de git)
├── includes/               # DB, auth, CSRF, mailer y cliente SMTP
├── models/                 # User, Treatment, Appointment, Favorite
├── templates/              # Componentes PHP (header, hero, booking, modals, etc.)
├── css/styles.css          # Estilos
├── js/
│   ├── app.js              # Logica del sitio principal
│   └── dashboard.js        # Logica del panel admin
├── logs/emails.log         # Registro local de correos (excluido de git)
├── sql/schema.sql          # Schema completo de la base de datos
├── index.php               # Pagina principal
├── dashboard.php           # Panel de administracion
├── setup.php               # Wizard de instalacion automatica
├── galeria.php             # Galeria de imagenes
├── nosotros.php            # Pagina Sobre nosotros
└── ubicacion.php           # Pagina de ubicacion
```

## Usuarios de prueba

| Nombre            | Correo                    | Contrasena | Rol           |
|-------------------|---------------------------|------------|---------------|
| Admin Principal   | admin@hanulbeauty.co      | Admin123   | SuperAdmin    |
| Recepcion Hanul   | recepcion@hanulbeauty.co  | Recep123   | Recepcionista |
| Sofia R.          | sofia@hanulbeauty.co      | Sofia123   | Esteticista   |
| Diomar A.         | diomar@hanulbeauty.co     | Diomar123  | Esteticista   |
| Cliente de Prueba | cliente@ejemplo.com       | Cliente123 | Cliente       |

## Funcionalidades

### Clientes
- Agendar citas **sin necesidad de registro** (solo nombre, correo y telefono)
- Horarios dinamicos: solo muestra las horas disponibles segun servicio y especialista
- Ver, cancelar citas y gestionar perfil (si crea cuenta)
- Guardar tratamientos como favoritos

### Administracion (SuperAdmin / Recepcionista / Esteticista)
- Dashboard con tabla de reservas, filtros y estadisticas
- Cambiar estado de reservas (Pendiente, Confirmada, Completada, Cancelada, etc.)
- Redireccion automatica al dashboard al iniciar sesion

### Seguridad
- Passwords con bcrypt
- CSRF tokens en todos los formularios
- Proteccion contra session fixation
- Headers de seguridad (CSP, X-Frame-Options, etc.)
- Acceso HTTP bloqueado a carpetas internas

## Licencia

Proyecto academico.
