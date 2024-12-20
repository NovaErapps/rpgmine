<?php
session_start();

// Limpiar todas las variables de sesión
$_SESSION = [];

// Eliminar la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Regenerar un nuevo ID de sesión para mayor seguridad
session_start();
session_regenerate_id(true);

// Redirigir al inicio
header("Location: index.php");
exit;
