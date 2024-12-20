<?php
session_start();
require_once 'db_connect.php';

// Verificar si el usuario y el personaje están en sesión
if (!isset($_SESSION['user_id']) || !isset($_SESSION['character_id'])) {
    header("Location: index.php");
    exit;
}

// Variables de sesión del jugador
$user_id = $_SESSION['user_id'];
$character_id = $_SESSION['character_id'];

// Cargar información del personaje
$stmt = $conn->prepare("SELECT name, class, biome, avatar, hp, hunger, level, xp FROM characters WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $character_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$character = $result->fetch_assoc();

// Si no se encuentra el personaje
if (!$character) {
    header("Location: dashboard.php?error=character_not_found");
    exit;
}

// Asignar datos del personaje
$name = $character['name'];
$class = $character['class'];
$biome = $character['biome'];
$avatar = $character['avatar'];
$hp = $character['hp'];
$hunger = $character['hunger'];
$level = $character['level'];
$xp = $character['xp'];

// Generar narrativa basada en el bioma y acción
$action = $_GET['action'] ?? 'explore';
$narrative = "";

switch ($action) {
    case 'explore':
        $narrative = "Te adentras en el bioma <strong>{$biome}</strong>, enfrentándote a los misterios de la naturaleza.";
        break;
    case 'collect':
        $narrative = "Recolectas recursos esenciales en <strong>{$biome}</strong>, asegurándote de que tu inventario esté listo.";
        break;
    case 'fight':
        $narrative = "Encuentras un enemigo en <strong>{$biome}</strong>. Prepárate para el combate.";
        break;
    default:
        $narrative = "Explora el mundo y encuentra nuevas aventuras.";
        break;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aventura en <?= htmlspecialchars($biome) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="gameContainer">
        <header>
            <h1>Aventura en <?= htmlspecialchars($biome) ?></h1>
        </header>
        <div id="hud">
            <div class="hud-section">
                <p>Vida: <?= str_repeat("❤️", $hp / 2) ?></p>
                <p>Hambre: <?= str_repeat("🍗", $hunger) ?></p>
            </div>
            <div class="hud-section">
                <p>Clase: <?= htmlspecialchars($class) ?></p>
                <p>Nivel: <?= $level ?></p>
            </div>
        </div>
        <div id="narrativeContainer">
            <p><?= $narrative ?></p>
        </div>
        <div id="optionsContainer">
            <a href="game.php?action=explore" class="option-btn">Explorar</a>
            <a href="game.php?action=collect" class="option-btn">Recolectar</a>
            <a href="game.php?action=fight" class="option-btn">Combatir</a>
        </div>
    </div>
</body>
</html>
