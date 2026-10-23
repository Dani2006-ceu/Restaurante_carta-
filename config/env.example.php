<?php
// Copia este archivo como config/env.php y pon tus datos.
// En InfinityFree los encuentras en: Panel de cliente > tu cuenta > MySQL Databases.
return [
    'APP_DB_HOST' => 'sql123.infinityfree.com', // "MySQL Hostname"
    'APP_DB_PORT' => '3306',
    'APP_DB_NAME' => 'if0_12345678_menu',       // "MySQL DB Name"
    'APP_DB_USER' => 'if0_12345678',            // "MySQL Username"
    'APP_DB_PASSWORD' => 'tu_contraseña',       // contraseña de la cuenta de hosting (vPanel)

    // Nombre que aparece junto al logo y en la app instalada.
    'APP_NAME' => 'Carta Digital',

    // Administrador: es quien autoriza la creación de usuarios nuevos.
    // La cuenta se crea sola la primera vez que se abre el sitio.
    'APP_ADMIN_EMAIL' => 'admin@gmail.com',
    'APP_ADMIN_PASSWORD' => 'contraseña_del_administrador',

    // Opcional: la dirección de tu sitio, sin "/" al final.
    // Si la dejas vacía se detecta sola.
    'APP_BASE_URL' => '',

    // Opcional: InfinityFree no permite enviar correos con mail(),
    // así que la recuperación de contraseña por correo no funcionará allí.
    'APP_MAIL_FROM' => '',
];
