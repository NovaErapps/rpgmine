<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        // Obtener la conexión desde el Singleton PDO
        $db = DatabaseConnection::getInstance();
        $conn = $db->getConnection();

        // Preparar la consulta SQL para buscar el usuario
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = :usernameOrEmail OR email = :usernameOrEmail");
        $stmt->bindParam(':usernameOrEmail', $usernameOrEmail, PDO::PARAM_STR);
        $stmt->execute();

        // Verificar si se encontró el usuario
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar la contraseña
            if (password_verify($password, $user['password'])) {
                // Usuario autenticado correctamente
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                // Redirigir a afterLogin.php
                header("Location: afterLogin.php");
                exit;
            }
        }

        // Si no se encontró el usuario o la contraseña es incorrecta
        $_SESSION['login_error'] = "Credenciales incorrectas.";
        header("Location: index.php");
        exit;

    } catch (PDOException $e) {
        // Registrar el error en un archivo de log
        error_log("Error en login.php: " . $e->getMessage(), 3, __DIR__ . '/errors.log');
        
        // Redirigir a una página de error genérica
        $_SESSION['login_error'] = "Ocurrió un problema. Inténtalo más tarde.";
        header("Location: index.php");
        exit;
    }
} else {
    // Si el método no es POST, redirigir al inicio
    header("Location: index.php");
    exit;
}
?>
