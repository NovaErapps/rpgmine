<?php
session_start();
require_once 'db_connect.php';

$selection_background = "images/character_selection.jpg";

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Obtener la conexión PDO
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();

    // Consultar los personajes del usuario
    $stmt = $conn->prepare("SELECT id, name, class, biome, avatar FROM characters WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $characters = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Registrar el error y mostrar un mensaje amigable
    error_log("Error en characterSelect.php: " . $e->getMessage(), 3, __DIR__ . '/errors.log');
    echo "Ocurrió un error al cargar los personajes. Por favor, intenta más tarde.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selecciona tu Personaje</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: url('<?= htmlspecialchars($selection_background) ?>') no-repeat center center fixed;
            background-size: cover;
            font-family: Arial, sans-serif;
            color: #fff;
            margin: 0;
        }
        header h1 {
            text-align: center;
            margin-top: 20px;
            font-size: 2rem;
            text-shadow: 2px 2px 10px #000;
        }
        .character-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            padding: 20px;
        }
        .character-card {
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #ff8c00;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            width: 250px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
        }
        .character-card img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        .select-btn, .delete-btn {
            padding: 10px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .select-btn {
            background: linear-gradient(45deg, #28a745, #218838);
            color: #fff;
            border: none;
        }
        .select-btn:hover {
            transform: scale(1.05);
            background: linear-gradient(45deg, #2ecc71, #27ae60);
        }
        .delete-btn {
            background: #ff4c4c;
            color: #fff;
            border: none;
        }
        .delete-btn:hover {
            background: #e60000;
            transform: scale(1.05);
        }
        .create-btn {
            display: inline-block;
            background: linear-gradient(45deg, #ffa500, #ff7500);
            color: #fff;
            padding: 15px 30px;
            font-size: 1.2rem;
            font-weight: bold;
            border-radius: 20px;
            text-decoration: none;
            transition: transform 0.3s, background 0.3s;
        }
        .create-btn:hover {
            background: linear-gradient(45deg, #ff8c00, #ff6600);
            transform: scale(1.1);
        }
        .new-character {
            margin-top: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <h1>Selecciona tu Personaje</h1>
    </header>

    <div class="character-list">
        <?php if (!empty($characters)): ?>
            <?php foreach ($characters as $character): ?>
                <div class="character-card">
                    <img src="<?= htmlspecialchars($character['avatar']) ?>" alt="Avatar del Personaje">
                    <h3><?= htmlspecialchars($character['name']) ?></h3>
                    <p>Clase: <?= htmlspecialchars($character['class']) ?></p>
                    <p>Bioma: <?= htmlspecialchars($character['biome']) ?></p>
                    <form action="selectCharacter.php" method="POST" style="margin-bottom: 10px;">
                        <input type="hidden" name="character_id" value="<?= $character['id'] ?>">
                        <button type="submit" class="select-btn">Continuar</button>
                    </form>
                    <form action="deleteCharacter.php" method="POST" onsubmit="return confirmDelete();">
                        <input type="hidden" name="character_id" value="<?= $character['id'] ?>">
                        <button type="submit" class="delete-btn">Eliminar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; font-size: 1.2rem;">No tienes personajes aún. ¡Crea uno ahora!</p>
        <?php endif; ?>
    </div>

    <div class="new-character">
        <a href="characterCreation.php" class="create-btn">Crear Nuevo Personaje</a>
    </div>

    <script>
        // Confirmar la eliminación de un personaje
        function confirmDelete() {
            return confirm("¿Estás seguro de que quieres borrar tu personaje? Esta acción no se puede deshacer.");
        }
    </script>
</body>
</html>
