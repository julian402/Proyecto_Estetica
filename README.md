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

## Estructura del proyecto

```
Proyecto_Estetica/
├── api/                    # Endpoints REST (JSON)
│   ├── auth/               # login, register, logout, profile
│   ├── appointments/       # create, list, cancel, all, update-status, availability
│   └── favorites/          # list, toggle
├── config/                 # Credenciales DB (excluido de git)
├── includes/               # DB connection, auth helpers, CSRF
├── models/                 # User, Treatment, Appointment, Favorite
├── templates/              # Componentes PHP (header, hero, booking, modals, etc.)
├── css/styles.css          # Estilos
├── js/
│   ├── app.js              # Logica del sitio principal
│   └── dashboard.js        # Logica del panel admin
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
