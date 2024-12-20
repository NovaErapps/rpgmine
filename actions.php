<?php
session_start();
require_once 'db_connect.php';
require_once 'security_functions.php'; // Asumiendo que tienes funciones de seguridad definidas

header('Content-Type: application/json');

if (!checkAuth()) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$character_id = $_SESSION['character_id'];

$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

// CSRF Token Validation
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'CSRF Token inválido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;

if ($action === 'equip_item') {
    $item_id = $data['item_id'] ?? null;
    $slot_name = $data['slot_name'] ?? 'weapon';

    try {
        if (!$item_id || !in_array($slot_name, ['head', 'body', 'weapon', 'boots'])) {
            throw new Exception('Datos de ítem o slot inválidos');
        }

        // Verificar que el personaje tenga el ítem
        $stmt = $conn->prepare("SELECT quantity, type FROM character_inventory JOIN items ON character_inventory.item_id = items.id WHERE character_id = :char_id AND item_id = :item_id");
        $stmt->execute([':char_id' => $character_id, ':item_id' => $item_id]);
        $invItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invItem || $invItem['quantity'] <= 0) {
            throw new Exception('No tienes ese ítem');
        }

        // Equipar: borrar slot actual, insertar nuevo
        $conn->beginTransaction();
        
        $conn->prepare("DELETE FROM equipment_slots WHERE character_id = :char_id AND slot_name = :slot_name")
             ->execute([':char_id' => $character_id, ':slot_name' => $slot_name]);

        $conn->prepare("INSERT INTO equipment_slots (character_id, slot_name, item_id) VALUES (:char_id, :slot_name, :item_id)")
             ->execute([':char_id' => $character_id, ':slot_name' => $slot_name, ':item_id' => $item_id]);

        // Ajustar stats según el ítem equipado (ejemplo sencillo)
        updateCharacterStats($conn, $character_id);

        $conn->commit();
        echo json_encode(buildResponse($conn, $character_id));
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'use_item') {
    $item_id = $data['item_id'] ?? null;
    if (!$item_id) {
        echo json_encode(['success' => false, 'error' => 'Falta item_id']);
        exit;
    }

    try {
        // Verificar que el personaje tenga el ítem y su tipo
        $stmt = $conn->prepare("SELECT character_inventory.quantity, items.type, items.name FROM character_inventory 
                                JOIN items ON character_inventory.item_id = items.id
                                WHERE character_id = :char_id AND item_id = :item_id");
        $stmt->execute([':char_id' => $character_id, ':item_id' => $item_id]);
        $invItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invItem || $invItem['quantity'] <= 0) {
            throw new Exception('No tienes ese ítem');
        }

        // Aplicar efecto según el tipo
        $effectApplied = applyItemEffect($conn, $character_id, $item_id, $invItem['name'], $invItem['type']);

        if ($effectApplied) {
            // Consumir ítem
            $conn->prepare("UPDATE character_inventory SET quantity = quantity - 1 WHERE character_id = :char_id AND item_id = :item_id")
                ->execute([':char_id' => $character_id, ':item_id' => $item_id]);
            $conn->exec("DELETE FROM character_inventory WHERE character_id = $character_id AND item_id = $item_id AND quantity <= 0");
        }

        echo json_encode(buildResponse($conn, $character_id));
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción desconocida']);

// Funciones auxiliares
function checkAuth() {
    return isset($_SESSION['user_id']) && isset($_SESSION['character_id']);
}

function updateCharacterStats($conn, $char_id) {
    $baseStats = ['strength' => 5, 'defense' => 5, 'agility' => 5];
    $bonuses = ['strength' => 0, 'defense' => 0, 'agility' => 0];

    $eqStmt = $conn->prepare("SELECT items.id, items.name FROM equipment_slots JOIN items ON equipment_slots.item_id = items.id WHERE character_id = :char_id");
    $eqStmt->execute([':char_id' => $char_id]);
    $eqItems = $eqStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($eqItems as $ei) {
        // Aquí deberías tener lógica para cada ítem según su ID o nombre
        switch ($ei['id']) {
            case 1: // Espada Hierro
                $bonuses['strength'] += 2;
                break;
            case 2: // Escudo Madera
                $bonuses['defense'] += 1;
                break;
            // Agrega más casos para otros ítems
        }
    }

    $newStats = [];
    foreach ($baseStats as $stat => $value) {
        $newStats[$stat] = $value + $bonuses[$stat];
    }

    $conn->prepare("UPDATE characters SET strength = :str, defense = :def, agility = :agi WHERE id = :char_id")
         ->execute([':str' => $newStats['strength'], ':def' => $newStats['defense'], ':agi' => $newStats['agility'], ':char_id' => $char_id]);
}

function applyItemEffect($conn, $char_id, $item_id, $item_name, $item_type) {
    $charStats = $conn->query("SELECT hp, hunger, strength, defense, agility, xp, level FROM characters WHERE id = $char_id")->fetch(PDO::FETCH_ASSOC);

    $newHP = $charStats['hp'];
    $newHunger = $charStats['hunger'];
    $newStr = $charStats['strength'];
    $newDef = $charStats['defense'];
    $newAgi = $charStats['agility'];

    if (strpos(strtolower($item_name), 'manzana') !== false) {
        $newHunger = min($charStats['hunger'] + 2, 10);
    } elseif (strpos(strtolower($item_name), 'pescado cocido') !== false) {
        $newHunger = min($charStats['hunger'] + 3, 10);
    } elseif (strpos(strtolower($item_name), 'poción') !== false) {
        $newHP = min($charStats['hp'] + 10, 20);
    } elseif (strpos(strtolower($item_name), 'elixir de fuerza') !== false) {
        $newStr += 3;
    }

    $conn->prepare("UPDATE characters SET hp = :hp, hunger = :hunger, strength = :str, defense = :def, agility = :agi WHERE id = :char_id")
         ->execute([':hp' => $newHP, ':hunger' => $newHunger, ':str' => $newStr, ':def' => $newDef, ':agi' => $newAgi, ':char_id' => $char_id]);

    return true;
}

function buildResponse($conn, $char_id) {
    $c = $conn->query("SELECT hp, hunger, strength, defense, agility, xp, level FROM characters WHERE id = $char_id")->fetch(PDO::FETCH_ASSOC);

    $eq = $conn->prepare("SELECT slot_name, items.name FROM equipment_slots JOIN items ON equipment_slots.item_id = items.id WHERE character_id = :char_id");
    $eq->execute([':char_id' => $char_id]);
    $eqData = $eq->fetchAll(PDO::FETCH_ASSOC);
    $equipment = array_column($eqData, 'name', 'slot_name');

    $inv = $conn->prepare("SELECT items.id as item_id, items.name, items.description, character_inventory.quantity, items.type FROM character_inventory JOIN items ON character_inventory.item_id = items.id WHERE character_inventory.character_id = :char_id");
    $inv->execute([':char_id' => $char_id]);
    $inventory = $inv->fetchAll(PDO::FETCH_ASSOC);

    return [
        'success' => true,
        'character' => $c,
        'equipment' => $equipment,
        'inventory' => $inventory
    ];
}