<?php
session_start();

// Eliminamos los datos guardados en la sesión.
$_SESSION = [];

// Eliminamos también la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $parametros = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parametros["path"],
        $parametros["domain"],
        $parametros["secure"],
        $parametros["httponly"]
    );
}

// Destruimos la sesión.
session_destroy();

// Volvemos a la página de inicio de sesión.
header("Location: login.php");
exit;