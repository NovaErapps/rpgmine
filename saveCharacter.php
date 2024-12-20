<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y limpiar las entradas
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $avatar = filter_input(INPUT_POST, 'avatar', FILTER_SANITIZE_URL);
    $class = filter_input(INPUT_POST, 'class', FILTER_SANITIZE_STRING);

    // Como el usuario solicitó no usar la lógica de bioma, ponemos un valor fijo "Inicio"
    // porque la columna es NOT NULL en la BD.
    $biome = "Inicio";

    // Validar si los campos están completos
    if (empty($name) || empty($avatar) || empty($class)) {
        echo "Faltan datos del personaje. <a href='characterCreation.php'>Volver</a>";
        exit;
    }

    // Inicializar valores predeterminados del personaje
    $hp = 20;
    $hunger = 10;
    $level = 1;
    $xp = 0;

    try {
        // Obtener conexión PDO
        $db = DatabaseConnection::getInstance();
        $conn = $db->getConnection();

        // Insertar el personaje en la base de datos
        // Incluimos 'biome' con valor fijo "Inicio" para no romper el NOT NULL del campo.
        $stmt = $conn->prepare("INSERT INTO characters (user_id, name, class, biome, avatar, hp, hunger, level, xp) 
                                VALUES (:user_id, :name, :class, :biome, :avatar, :hp, :hunger, :level, :xp)");

        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':class', $class, PDO::PARAM_STR);
        $stmt->bindParam(':biome', $biome, PDO::PARAM_STR);
        $stmt->bindParam(':avatar', $avatar, PDO::PARAM_STR);
        $stmt->bindParam(':hp', $hp, PDO::PARAM_INT);
        $stmt->bindParam(':hunger', $hunger, PDO::PARAM_INT);
        $stmt->bindParam(':level', $level, PDO::PARAM_INT);
        $stmt->bindParam(':xp', $xp, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $character_id = $conn->lastInsertId();

            // Según la clase, asignar el inventario inicial
            if ($class === 'Explorador') {
                // Explorador: Mapa Avanzado (9), Bloques de Madera (13)x3, Manzana (3)
                $conn->exec("INSERT INTO character_inventory (character_id, item_id, quantity) VALUES
                             ($character_id, 9, 1),
                             ($character_id, 13, 3),
                             ($character_id, 3, 1)");
            } elseif ($class === 'Guerrero') {
                // Guerrero: Espada de Hierro (1), Escudo de Madera (2), Pescado Cocido (10)
                $conn->exec("INSERT INTO character_inventory (character_id, item_id, quantity) VALUES
                             ($character_id, 1, 1),
                             ($character_id, 2, 1),
                             ($character_id, 10, 1)");
            } elseif ($class === 'Recolector') {
                // Recolector: Pico de Piedra (4), Mochila Básica (12), Pan (11)
                $conn->exec("INSERT INTO character_inventory (character_id, item_id, quantity) VALUES
                             ($character_id, 4, 1),
                             ($character_id, 12, 1),
                             ($character_id, 11, 1)");
            }
            
            if ($class === 'Explorador') {
    $conn->exec("INSERT INTO character_skills (character_id, skill_name, level) VALUES
                 ($character_id, 'Golpe Fuerte', 1),
                 ($character_id, 'Puntería', 1)");
} elseif ($class === 'Guerrero') {
    $conn->exec("INSERT INTO character_skills (character_id, skill_name, level) VALUES
                 ($character_id, 'Bloqueo Sólido', 1),
                 ($character_id, 'Golpe Fuerte', 1)");
} elseif ($class === 'Recolector') {
    // Tal vez menos habilidades de combate y más orientadas a recolección
    $conn->exec("INSERT INTO character_skills (character_id, skill_name, level) VALUES
                 ($character_id, 'Recolección Rápida', 1)");
}


            // Guardar en sesión el character_id si es necesario
            $_SESSION['character_id'] = $character_id;

            // Redirigir al intro (o al HUD) una vez creado el personaje con inventario inicial
            header("Location: intro.php");
            exit;
        } else {
            throw new Exception("Error al insertar el personaje en la base de datos.");
        }
    } catch (PDOException $e) {
        // Registrar error y mostrar mensaje amigable
        error_log("Error en saveCharacter.php: " . $e->getMessage(), 3, __DIR__ . '/errors.log');
        echo "Ocurrió un problema al crear tu personaje. Inténtalo de nuevo más tarde.";
    } catch (Exception $e) {
        echo $e->getMessage();
    }

} else {
    header("Location: characterCreation.php");
    exit;
}
