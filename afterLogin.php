<?php
session_start();

require_once 'db_connect.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Obtener la conexión usando el Singleton
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();

    // Consulta para verificar si el usuario tiene personajes
    $stmt = $conn->prepare("SELECT id FROM characters WHERE user_id = :user_id LIMIT 1");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Verificar si se encontraron resultados
    if ($stmt->rowCount() > 0) {
        // Redirigir a characterSelect.php si hay personajes
        header("Location: characterSelect.php");
        exit;
    } else {
        // Si no hay personajes, redirigir a characterCreation.php
        header("Location: characterCreation.php");
        exit;
    }
} catch (Exception $e) {
    // Manejo de errores: mostrar un mensaje y registrar el error
    error_log("Error al obtener personajes: " . $e->getMessage(), 3, __DIR__ . '/errors.log');
    echo "Ocurrió un error al cargar tus personajes. Por favor, inténtalo de nuevo más tarde.";
    exit;
}
?>
