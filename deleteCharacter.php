<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $character_id = filter_input(INPUT_POST, 'character_id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$character_id) {
        // ID inválido
        $_SESSION['delete_message'] = "ID de personaje inválido.";
        header("Location: characterSelect.php");
        exit;
    }

    try {
        // Obtener la conexión PDO
        $db = DatabaseConnection::getInstance();
        $conn = $db->getConnection();

        // Verificar que el personaje pertenece al usuario
        $stmt = $conn->prepare("SELECT id FROM characters WHERE id = :character_id AND user_id = :user_id");
        $stmt->bindParam(':character_id', $character_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Eliminar el personaje
            $deleteStmt = $conn->prepare("DELETE FROM characters WHERE id = :character_id AND user_id = :user_id");
            $deleteStmt->bindParam(':character_id', $character_id, PDO::PARAM_INT);
            $deleteStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if ($deleteStmt->execute()) {
                $_SESSION['delete_message'] = "Personaje eliminado correctamente.";
            } else {
                $_SESSION['delete_message'] = "Error al eliminar el personaje.";
            }
        } else {
            // Intento de eliminación no autorizado
            $_SESSION['delete_message'] = "No estás autorizado para eliminar este personaje.";
        }
    } catch (PDOException $e) {
        // Registrar el error en un archivo de log
        error_log("Error en deleteCharacter.php: " . $e->getMessage(), 3, __DIR__ . '/errors.log');
        $_SESSION['delete_message'] = "Ocurrió un problema al eliminar el personaje. Inténtalo más tarde.";
    }

    // Redirigir a la página de selección de personajes
    header("Location: characterSelect.php");
    exit;
} else {
    // Si no es POST, redirigir al selector de personajes
    header("Location: characterSelect.php");
    exit;
}
?>
