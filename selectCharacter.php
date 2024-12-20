<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    // Redirigir si el usuario no está autenticado
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['character_id'])) {
    $character_id = intval($_POST['character_id']);
    $user_id = $_SESSION['user_id'];

    try {
        // Obtener conexión usando PDO
        $db = DatabaseConnection::getInstance();
        $conn = $db->getConnection();

        // Verificar si el personaje pertenece al usuario
        $stmt = $conn->prepare("SELECT id FROM characters WHERE id = :character_id AND user_id = :user_id");
        $stmt->bindParam(':character_id', $character_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Guardar el ID del personaje en la sesión
            $_SESSION['character_id'] = $character_id;

            // Redirigir al dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            // Mensaje si el personaje no pertenece al usuario
            $_SESSION['error'] = "El personaje seleccionado no es válido.";
            header("Location: characterSelect.php");
            exit;
        }
    } catch (PDOException $e) {
        // Registrar errores en un archivo
        error_log("Error en selectCharacter.php: " . $e->getMessage(), 3, __DIR__ . '/errors.log');

        // Mensaje de error genérico
        $_SESSION['error'] = "Ocurrió un error al seleccionar el personaje. Inténtalo más tarde.";
        header("Location: characterSelect.php");
        exit;
    }
} else {
    // Redirigir si no es una solicitud válida
    header("Location: index.php");
    exit;
}
?>
