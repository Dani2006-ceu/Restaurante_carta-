# LoginB: arquitectura MVC

Aplicación inicial para PHP 8+ y MariaDB en XAMPP. El punto de entrada es `index.php`; no requiere framework ni reglas de reescritura de Apache.

## Estructura

```text
LoginB/
├── index.php
├── config/
│   ├── bootstrap.php
│   ├── database.php
│   └── helpers.php
├── controllers/
│   ├── LoginController.php
│   ├── RegisterController.php
│   └── PasswordRecoveryController.php
├── models/
│   ├── User.php
│   └── PasswordResetToken.php
├── views/
│   ├── layouts/main.php
│   ├── home.php
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── recover.php
│   │   └── reset-password.php
│   └── errors/
├── assets/
│   ├── index.css
│   └── js.js
└── db/
    ├── login.db       # SQLite anterior, no se usa con la conexión MariaDB
    └── schema.sql     # Esquema MariaDB versionado
```

## Responsabilidades

- `index.php` despacha las rutas y conecta los modelos con sus controladores.
- `controllers/` valida las solicitudes y decide qué vista mostrar.
- `models/` contiene las consultas preparadas para MariaDB.
- `views/` presenta la interfaz; el layout comparte la estructura HTML.
- `config/` inicia la sesión, ofrece funciones compartidas y configura PDO.
- `db/schema.sql` documenta y crea las tablas `users` y `password_reset_tokens` al conectar.

## Ejecutar en XAMPP

1. Enciende Apache y MySQL desde el panel de XAMPP.
2. Crea la base `loginb_db` en phpMyAdmin con cotejamiento `utf8mb4_unicode_ci`.
3. La conexión local predeterminada es `127.0.0.1:3306`, usuario `root` y contraseña vacía. Si tu instalación usa otras credenciales, configura `APP_DB_HOST`, `APP_DB_PORT`, `APP_DB_NAME`, `APP_DB_USER` y `APP_DB_PASSWORD` en el entorno de Apache. No guardes una contraseña real en Git.
4. Comprueba que `pdo_mysql` esté habilitada en PHP y abre `http://localhost/LoginB/`.
5. Crea una cuenta desde **Crear una cuenta** e inicia sesión.

La conexión crea las tablas desde `db/schema.sql` al primer acceso. Las rutas usan el parámetro `route`: `login`, `register`, `recover`, `reset-password`, `home` y `logout`.

## Recuperación por correo

El flujo genera un token aleatorio de un solo uso, guarda únicamente su hash y lo vence después de una hora. Para enviar el enlace:

- Configura la variable de entorno `APP_MAIL_FROM` con un correo válido.
- Configura el transporte de correo de PHP en `php.ini` (en Windows, normalmente `SMTP` y `sendmail_from`) y reinicia Apache.
- Si la aplicación no está en `http://localhost/LoginB`, configura `APP_BASE_URL` con la URL pública base, sin `/` al final.

La respuesta de recuperación es genérica para no revelar si un correo está registrado. Los errores de entrega quedan en el log de PHP/Apache. Usa un proveedor SMTP confiable antes de desplegar en producción.

## Menú digital del restaurante

- `index.php` (ruta `inicio`) es la página pública de presentación; arriba tiene el botón **Iniciar sesión**.
- Solo el administrador (`APP_ADMIN_EMAIL`, por defecto `admindash@gmail.com`) autoriza usuarios nuevos: el formulario de registro pide su correo y contraseña. La cuenta del administrador se crea sola con `APP_ADMIN_PASSWORD` si todavía no existe.
- Con sesión iniciada: **Mi menú** (`menu`) para agregar productos (categoría, nombre, descripción, foto por link, precio, descuento, disponible), datos del restaurante y **Generar link** con QR; **Pedidos** (`pedidos`) muestra lo que piden los clientes y avisa con sonido.
- Los clientes abren `index.php?route=carta&m=<token>` desde el link o el QR: solo ven el menú (pensado para celular) y el botón **Pedir**. Generar un link nuevo invalida el anterior.
- Tablas: `restaurants`, `menu_items`, `orders` y `order_items` en `db/schema.sql`.
- Cuando llega un pedido suena un timbre en el panel (botón **🔔 Sonido** para activarlo/desactivarlo) y, si el panel está en otra pestaña, aparece una notificación del sistema.
- Diseño: `assets/app.css` (sitio y panel, tonos espresso/crema/dorado/vino), `assets/carta.css` (menú del cliente en papel crema) y `assets/fondo.js` (brasas doradas). Logo en `assets/img/`.

### Descargar como app (PWA)

- El panel y el menú del cliente se pueden instalar en el celular o la computadora (botones **Descargar app** y **Guardar en el celular**). En iPhone se instala desde Compartir → *Agregar a pantalla de inicio*.
- `sw.js` guarda el diseño y el último menú visto para abrirlo sin conexión; el panel y los pedidos siempre usan la red.
- El cliente también puede **Descargar PDF** del menú (usa el diálogo de impresión del navegador).
- Instalar requiere **HTTPS** (o `localhost`). En InfinityFree activa el certificado SSL gratuito de tu dominio.

### QR desde XAMPP

El link del menú usa la dirección con la que abres el panel. Con `localhost` no abre desde el celular: el panel muestra la dirección de tu PC en la red (por ejemplo `http://192.168.0.113/...`); ábrelo desde ahí con el celular en el mismo Wi-Fi. Si no carga, permite Apache en el Firewall de Windows.

## Subir a InfinityFree

1. En el panel de InfinityFree crea la base de datos en **MySQL Databases** y anota *MySQL Hostname*, *DB Name*, *Username* y la contraseña de la cuenta (vPanel).
2. Copia `config/env.example.php` como `config/env.php` y pon esos datos, el correo y la contraseña del administrador.
3. Sube **todo el contenido** de esta carpeta (no la carpeta en sí) dentro de `htdocs` con FileZilla o el *Online File Manager*, incluidos los `.htaccess`. No hace falta subir `.git`.
4. (Opcional) En phpMyAdmin importa `db/schema.sql`; si no, las tablas se crean solas en la primera visita.
5. En el panel de InfinityFree activa **SSL** (certificado gratuito) para que funcionen la instalación como app y el QR con `https://`.
6. Abre tu dominio, pulsa **Iniciar sesión** y entra con la cuenta del administrador.

InfinityFree no permite `mail()`, así que la recuperación de contraseña por correo no envía mensajes allí.

## Seguridad incluida en esta base

- Hash de contraseñas con `password_hash()` y verificación con `password_verify()`.
- Consultas PDO preparadas, token CSRF en formularios y regeneración del ID de sesión al iniciar sesión.
- Cookie de sesión `HttpOnly`, `SameSite=Lax` y `Secure` cuando la conexión usa HTTPS.
- Tokens de recuperación aleatorios, con hash en base de datos, vencimiento y uso único.
- Apache bloquea el acceso HTTP directo a configuración, modelos, controladores, vistas y archivos dentro de `db` mediante `.htaccess`.
- `db/login.db` es un archivo SQLite anterior que ya no se utiliza. El esquema MariaDB se conserva en Git; no subas datos de usuarios.

La base es un punto de partida para desarrollo, no una solución lista para producción. Antes de desplegar, añade límites de intentos, configuración de HTTPS, política de contraseñas apropiada, gestión de errores/logs y pruebas.

## Trabajo en equipo

- Login: `LoginController.php` y `views/auth/login.php`.
- Registro: `RegisterController.php` y `views/auth/register.php`.
- Recuperación: `PasswordRecoveryController.php` y las vistas `recover.php` / `reset-password.php`.
- Coordinen los cambios compartidos en `User.php`, `database.php` y `schema.sql`; integren mediante pull requests hacia `main`.
