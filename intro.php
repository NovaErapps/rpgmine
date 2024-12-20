<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['character_id'])) {
    header("Location: index.php");
    exit;
}

// Obtener datos del personaje
require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];
$character_id = $_SESSION['character_id'];

try {
    // Obtener conexión PDO
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();

    // Consulta para obtener el nombre del personaje
    $stmt = $conn->prepare("SELECT name FROM characters WHERE id = :character_id AND user_id = :user_id");
    $stmt->bindParam(':character_id', $character_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Obtener resultado
    $character = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$character) {
        throw new Exception("No se encontró el personaje seleccionado.");
    }

    $character_name = htmlspecialchars($character['name']);

} catch (PDOException $e) {
    error_log("Error en intro.php: " . $e->getMessage(), 3, __DIR__ . '/errors.log');
    echo "Ocurrió un error al cargar los datos del personaje. Inténtalo más tarde.";
    exit;
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: black;
            color: white;
            font-family: 'Arial', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .intro-container {
            text-align: center;
            animation: fade-in 2s ease-in-out, fade-out 2s ease-in-out 8s forwards;
        }

        h1 {
            font-size: 3rem;
            margin: 0;
        }

        p {
            font-size: 1.2rem;
            margin: 10px 0;
            line-height: 1.5;
        }

        @keyframes fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fade-out {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
</head>
<body>
    <div class="intro-container">
        <h1>Bienvenido a RPG Mine</h1>
        <p><?= $character_name ?>, despiertas en un bosque extraño. Lo único que recuerdas es que entraste a un portal misterioso.</p>
        <p>A lo lejos ves una cabaña destrozada, ¿será posible hacerla tu nuevo hogar?</p>
    </div>

    <script>
        setTimeout(() => {
            window.location.href = "dashboard.php";
        }, 9000); // Redirige después de 9 segundos
    </script>
</body>
</html>
