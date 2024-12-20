<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$creation_background = "images/character_creation.jpg";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creación de Personaje</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: url('<?= htmlspecialchars($creation_background) ?>') no-repeat center center fixed;
            background-size: cover;
        }
        .avatars img {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .avatars img.selected {
            transform: scale(1.2);
            border: 3px solid #28a745;
            border-radius: 10px;
        }
        button {
            margin-top: 15px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div id="app">
        <div id="characterCreation">
            <header>
                <h1>🛠️ CREACIÓN DE PERSONAJE 🛠️</h1>
            </header>
            <form id="characterForm" action="saveCharacter.php" method="POST">
                <div class="step">
                    <label for="name">Nombre del Personaje:</label>
                    <input type="text" name="name" id="name" pattern="^[a-zA-Z0-9_ ]{3,20}$" title="El nombre debe tener entre 3 y 20 caracteres y no contener símbolos especiales." required>
                </div>
                <div class="step">
                    <h3>Elige tu Avatar (Haz clic en una imagen)</h3>
                    <div class="avatars">
                        <?php 
                        // Generación segura de avatares
                        $avatars = [
                            "assets/explorer.png" => "Explorador",
                            "assets/miner.png" => "Recolector",
                            "assets/warrior.png" => "Guerrero"
                        ];
                        foreach ($avatars as $path => $alt): ?>
                            <label class="avatar-label">
                                <input type="radio" name="avatar" value="<?= htmlspecialchars($path) ?>" required>
                                <img src="<?= htmlspecialchars($path) ?>" class="avatar" alt="<?= htmlspecialchars($alt) ?>">
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="step">
                    <label for="class">Clase:</label>
                    <select name="class" id="class" required>
                        <option value="Explorador">Explorador</option>
                        <option value="Guerrero">Guerrero</option>
                        <option value="Recolector">Recolector</option>
                    </select>
                </div>
                <button type="submit">Crear Personaje</button>
            </form>
        </div>
    </div>

    <script>
        const avatarInputs = document.querySelectorAll('.avatar-label input');
        const avatarImages = document.querySelectorAll('.avatars img');
        const classSelector = document.getElementById('class');

        avatarInputs.forEach((input, index) => {
            input.addEventListener('change', () => {
                avatarImages.forEach(img => img.classList.remove('selected'));
                avatarImages[index].classList.add('selected');
                classSelector.value = avatarImages[index].alt;
            });
        });
    </script>
</body>
</html>
