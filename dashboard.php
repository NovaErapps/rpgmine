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

    // Obtener datos del personaje
    $stmt = $conn->prepare("SELECT * FROM characters WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $character_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $character = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$character) {
        header("Location: characterSelect.php");
        exit;
    }

    // Obtener equipamiento del personaje
    $equipStmt = $conn->prepare("SELECT slot_name, items.name as item_name, items.description 
                                 FROM equipment_slots 
                                 LEFT JOIN items ON equipment_slots.item_id = items.id
                                 WHERE character_id = :character_id");
    $equipStmt->bindParam(':character_id', $character_id, PDO::PARAM_INT);
    $equipStmt->execute();
    $equipmentData = $equipStmt->fetchAll(PDO::FETCH_ASSOC);

    // Reestructurar el equipamiento
    $equipment = [];
    if ($equipmentData) {
        foreach ($equipmentData as $eq) {
            $equipment[$eq['slot_name']] = [
                'name' => $eq['item_name'],
                'desc' => $eq['description']
            ];
        }
    }

    // Obtener habilidades
    $skillStmt = $conn->prepare("SELECT skill_name, level FROM character_skills WHERE character_id = :character_id");
    $skillStmt->bindParam(':character_id', $character_id, PDO::PARAM_INT);
    $skillStmt->execute();
    $skills = $skillStmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener inventario
    $inventoryStmt = $conn->prepare("SELECT items.name, items.description, character_inventory.quantity
                                     FROM character_inventory
                                     JOIN items ON character_inventory.item_id = items.id
                                     WHERE character_inventory.character_id = :character_id");
    $inventoryStmt->bindParam(':character_id', $character_id, PDO::PARAM_INT);
    $inventoryStmt->execute();
    $inventory = $inventoryStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error en dashboard: " . $e->getMessage());
    die("Error al cargar el dashboard.");
}

// Slots predefinidos
$slots = ['head' => 'Cabeza', 'body' => 'Cuerpo', 'weapon' => 'Arma', 'boots' => 'Botas'];

// Generar token CSRF
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-container {
            max-width: 1000px;
            margin: 40px auto;
            text-align: center;
            background: var(--dark-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 30px;
        }

        .character-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
            object-fit: cover;
            margin-bottom: 10px;
        }

        /* Tabs */
        .hud-tabs {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .hud-tabs button {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: var(--text-color);
            border: none;
            border-radius: var(--border-radius);
            padding: 10px 20px;
            font-size: 1.1rem;
            font-weight: bold;
            box-shadow: var(--shadow-light);
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .hud-tabs button:hover {
            background: linear-gradient(45deg, var(--highlight-color), var(--secondary-color));
            transform: scale(1.05);
            box-shadow: 0px 8px 20px rgba(0,0,0,0.4);
        }

        .hud-panel {
            display: none;
            background: var(--dark-bg);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            margin-top: 20px;
            transition: var(--transition-smooth);
        }

        .hud-panel.active {
            display: block;
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
            cursor: move;
        }

        .inventory-item:hover {
            transform: scale(1.1);
            border-color: var(--primary-color);
        }

        .equipment-grid {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }

        .equipment-slot {
            background: #333;
            border: 2px dashed #555;
            border-radius: var(--border-radius);
            padding: 20px;
            text-align: center;
            width: 100px;
            height: 100px;
        }

        .equipment-slot span {
            font-size: 0.9rem;
            color: var(--primary-color);
        }

        /* Tooltip */
        .tooltip {
            position: absolute;
            background: rgba(0,0,0,0.8);
            color: #fff;
            padding: 5px 10px;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 9999;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #333;
            color: #fff;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            opacity: 0;
            transition: opacity 0.5s;
            z-index: 1000;
        }

        .toast.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Bienvenido al Dashboard</h1>
        </header>

        <!-- Información del personaje -->
        <div class="profile-basic">
            <img src="<?= htmlspecialchars($character['avatar']); ?>" alt="Avatar" class="character-icon">
            <h2><?= htmlspecialchars($character['name']); ?> (<?= htmlspecialchars($character['class']); ?>)</h2>
            <p>⭐ Nivel: <?= $character['level']; ?> | 🎮 XP: <?= $character['xp']; ?></p>
            <p>❤️ Vida: <?= $character['hp']; ?> | 🍗 Hambre: <?= $character['hunger']; ?></p>
            <p>💪 Fuerza: <span class="strength-value"><?= $character['strength']; ?></span> | 🛡️ Defensa: <?= $character['defense']; ?> | 🏃 Agilidad: <?= $character['agility']; ?></p>
        </div>

        <!-- Tabs -->
        <div class="hud-tabs">
            <button data-tab="equipment-panel" aria-label="Equipamiento">Equipamiento</button>
            <button data-tab="inventory-panel" aria-label="Inventario">Inventario</button>
            <button data-tab="skills-panel" aria-label="Habilidades">Habilidades</button>
            <button data-tab="customization-panel" aria-label="Personalizar">Personalizar</button>
        </div>

        <!-- Panel Equipamiento -->
        <div id="equipment-panel" class="hud-panel active">
            <h3>🛡️ Equipamiento</h3>
            <div class="equipment-grid">
                <?php foreach ($slots as $key => $label): ?>
                    <div class="equipment-slot" slot_name="<?= $key ?>" ondragover="event.preventDefault()" ondrop="handleDrop(event)">
                        <span><?= $label ?></span><br>
                        <?= isset($equipment[$key]['name']) ? htmlspecialchars($equipment[$key]['name']) : "Vacío" ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Panel Inventario -->
        <div id="inventory-panel" class="hud-panel">
            <h3>🎒 Inventario</h3>
            <?php if ($inventory): ?>
                <div class="inventory-grid">
                    <?php foreach ($inventory as $item): ?>
                        <div class="inventory-item" 
                             data-item-id="<?= $item['id'] ?>" 
                             data-title="<?= htmlspecialchars($item['name'] . ": " . $item['description']); ?>" 
                             draggable="true">
                            <?= htmlspecialchars($item['name']) ?> x<?= $item['quantity'] ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Tu inventario está vacío.</p>
            <?php endif; ?>
        </div>

        <!-- Panel Habilidades -->
        <div id="skills-panel" class="hud-panel">
            <h3>⚔️ Habilidades</h3>
            <?php if ($skills): ?>
                <ul>
                    <?php foreach ($skills as $skill): ?>
                        <li><?= htmlspecialchars($skill['skill_name']) ?> (Nivel <?= $skill['level'] ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No tienes habilidades desbloqueadas aún.</p>
            <?php endif; ?>
        </div>

        <!-- Panel Personalizar -->
        <div id="customization-panel" class="hud-panel">
            <h3>🛠️ Personalizar Personaje</h3>
            <p>Puedes <a href="customizeCharacter.php">cambiar el nombre y el avatar</a> de tu personaje aquí.</p>
        </div>

        <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Tabs
            document.querySelectorAll('.hud-tabs button').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.hud-panel').forEach(panel => panel.classList.remove('active'));
                    const tabId = btn.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });

            // Toast
            function showToast(message) {
                const toast = document.createElement('div');
                toast.className = 'toast';
                toast.textContent = message;
                document.body.appendChild(toast);
                setTimeout(() => toast.classList.add('show'), 50);
                setTimeout(() => {
                    toast.classList.remove('show');
                    toast.remove();
                }, 3000);
            }

            // Equipar ítem vía AJAX
            function equipItem(itemId, slotName) {
                const csrfToken = document.getElementById('csrf_token').value;
                fetch('actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({action: 'equip_item', item_id: itemId, slot_name: slotName})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Ítem equipado con éxito');
                        updateEquipmentUI(data.newEquipment);
                    } else {
                        showToast('No se pudo equipar el ítem: ' + (data.error || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error al procesar la solicitud');
                });
            }

            function updateEquipmentUI(newEquipment) {
                for (const [slotName, item] of Object.entries(newEquipment)) {
                    const slotElement = document.querySelector(`.equipment-slot[slot_name="${slotName}"]`);
                    if (slotElement) {
                        slotElement.innerHTML = `<span>${slotName.charAt(0).toUpperCase() + slotName.slice(1)}</span><br>${item || 'Vacío'}`;
                    }
                }
            }

            // Drag & Drop para ítems
            const inventoryContainer = document.querySelector('.inventory-grid');
            inventoryContainer.addEventListener('dragstart', e => {
                if (e.target.classList.contains('inventory-item')) {
                    e.dataTransfer.setData('text/plain', e.target.dataset.itemId);
                }
            });

            document.querySelectorAll('.equipment-slot').forEach(slot => {
                slot.addEventListener('dragover', e => e.preventDefault());
                slot.addEventListener('drop', e => {
                    e.preventDefault();
                    const itemId = e.dataTransfer.getData('text');
                    const slotName = e.target.getAttribute('slot_name');
                    equipItem(itemId, slotName);
                });
            });

                    document.querySelectorAll('.equipment-slot').forEach(slot => {
                slot.addEventListener('dragover', e => e.preventDefault());
                slot.addEventListener('drop', e => {
                    e.preventDefault();
                    const itemId = e.dataTransfer.getData('text');
                    const slotName = e.target.getAttribute('slot_name');
                    equipItem(itemId, slotName);
                });
            });

            // Tooltips usando una librería (ejemplo con Tippy.js)
            if (typeof tippy !== 'undefined') {
                tippy('.inventory-item', {
                    content: (ref) => ref.getAttribute('data-title'),
                    placement: 'top',
                    delay: [100, 0],
                    arrow: true,
                    animation: 'shift-away'
                });
            } else {
                console.warn('Tippy.js no está disponible. Implementando tooltips básicos.');
                // Implementación básica de tooltips si Tippy.js no está incluido
                let tooltipEl = null;
                document.addEventListener('mouseover', e => {
                    const item = e.target.closest('.inventory-item');
                    if (item && item.dataset.title) {
                        if (!tooltipEl) {
                            tooltipEl = document.createElement('div');
                            tooltipEl.className = 'tooltip';
                            document.body.appendChild(tooltipEl);
                        }
                        tooltipEl.textContent = item.dataset.title;
                        tooltipEl.style.opacity = '1';
                    }
                });

                document.addEventListener('mousemove', e => {
                    if (tooltipEl) {
                        tooltipEl.style.top = (e.pageY + 10) + 'px';
                        tooltipEl.style.left = (e.pageX + 10) + 'px';
                    }
                });

                document.addEventListener('mouseout', e => {
                    const item = e.target.closest('.inventory-item');
                    if (item && tooltipEl) {
                        tooltipEl.style.opacity = '0';
                    }
                });
            }
        });
    </script>
</body>
</html>