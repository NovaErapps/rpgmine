<?php
session_start();
require_once 'db_connect.php';

// Verificar autenticación y obtener datos del personaje
if (!isset($_SESSION['user_id']) || !isset($_SESSION['character_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$character_id = $_SESSION['character_id'];

try {
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();

    // Obtener estadísticas y datos del personaje
    $stmt = $conn->prepare("SELECT * FROM characters WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $character_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $character = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$character) {
        header("Location: characterSelect.php");
        exit;
    }

    // Obtener equipamiento
    $equipStmt = $conn->prepare("SELECT slot_name, items.name as item_name, items.description 
                                FROM equipment_slots 
                                LEFT JOIN items ON equipment_slots.item_id = items.id
                                WHERE character_id = :character_id");
    $equipStmt->bindParam(':character_id', $character_id);
    $equipStmt->execute();
    $equipment = $equipStmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener habilidades
    $skillStmt = $conn->prepare("SELECT skill_name, level FROM character_skills WHERE character_id = :character_id");
    $skillStmt->bindParam(':character_id', $character_id);
    $skillStmt->execute();
    $skills = $skillStmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener inventario
    $inventoryStmt = $conn->prepare("SELECT items.name, items.description, character_inventory.quantity
                                     FROM character_inventory
                                     JOIN items ON character_inventory.item_id = items.id
                                     WHERE character_inventory.character_id = :character_id");
    $inventoryStmt->bindParam(':character_id', $character_id);
    $inventoryStmt->execute();
    $inventory = $inventoryStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error en HUD: " . $e->getMessage());
    die("Error al cargar el HUD.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HUD del Personaje</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: url('images/hud_background.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
            margin: 0; padding: 0;
        }

        .hud-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.6);
        }

        h2, h3 {
            color: #ffa500;
        }

        section {
            margin-bottom: 20px;
            padding: 10px;
            background: rgba(255, 165, 0, 0.1);
            border-radius: 10px;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            padding: 5px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .inventory-item {
            background: #333;
            padding: 10px;
            text-align: center;
            border: 1px solid #555;
            transition: transform 0.3s;
        }

        .inventory-item:hover {
            transform: scale(1.1);
            border-color: #ffa500;
        }

        .equipment-grid {
            display: flex;
            justify-content: space-around;
        }

        .equipment-slot {
            background: #333;
            border: 2px dashed #555;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            width: 100px;
            height: 100px;
        }

        .equipment-slot span {
            font-size: 0.9rem;
            color: #ffa500;
        }
    </style>
</head>
<body>
    <div class="hud-container">
        <!-- Perfil -->
        <section>
            <h2>👤 Perfil del Personaje</h2>
            <p>Nombre: <?= htmlspecialchars($character['name']) ?> (<?= htmlspecialchars($character['class']) ?>)</p>
            <p>❤️ Vida: <?= $character['hp'] ?> | 🍗 Hambre: <?= $character['hunger'] ?></p>
            <p>💪 Fuerza: <?= $character['strength'] ?> | 🛡️ Defensa: <?= $character['defense'] ?> | 🏃 Agilidad: <?= $character['agility'] ?></p>
            <p>⭐ Nivel: <?= $character['level'] ?> | 🎮 XP: <?= $character['xp'] ?> | 🔧 Puntos de habilidad: <?= $character['skill_points'] ?></p>
        </section>

        <!-- Equipamiento -->
        <section>
            <h3>🛡️ Equipamiento</h3>
            <div class="equipment-grid">
                <?php 
                $slots = ['head' => 'Cabeza', 'body' => 'Cuerpo', 'weapon' => 'Arma', 'boots' => 'Botas'];
                foreach ($slots as $key => $label): ?>
                    <div class="equipment-slot">
                        <span><?= $label ?></span><br>
                        <?= isset($equipment[$key]['item_name']) ? htmlspecialchars($equipment[$key]['item_name']) : "Vacío" ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Habilidades -->
        <section>
            <h3>⚔️ Habilidades</h3>
            <ul>
                <?php foreach ($skills as $skill): ?>
                    <li><?= htmlspecialchars($skill['skill_name']) ?> (Nivel <?= $skill['level'] ?>)</li>
                <?php endforeach; ?>
            </ul>
        </section>

        <!-- Inventario -->
        <section>
            <h3>🎒 Inventario</h3>
            <div class="inventory-grid">
                <?php foreach ($inventory as $item): ?>
                    <div class="inventory-item">
                        <?= htmlspecialchars($item['name']) ?> x<?= $item['quantity'] ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</body>
</html>
