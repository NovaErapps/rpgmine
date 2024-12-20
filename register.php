<?php
session_start();
require_once 'db_connect.php';

$errors = [];
$success = false;

// Procesamiento del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['regUsername']);
    $email = trim($_POST['regEmail']);
    $password = trim($_POST['regPassword']);

    // Validaciones del lado del servidor
    if (empty($username) || empty($email) || empty($password)) {
        $errors[] = "Todos los campos son obligatorios.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato del correo electrónico es inválido.";
    }

    if (!preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}/', $password)) {
        $errors[] = "La contraseña debe tener al menos 8 caracteres, una letra mayúscula, una minúscula y un número.";
    }

    if (empty($errors)) {
        try {
            $db = DatabaseConnection::getInstance();
            $conn = $db->getConnection();

            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $errors[] = "El nombre de usuario o correo ya está en uso.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
                $insert->bindParam(':username', $username);
                $insert->bindParam(':email', $email);
                $insert->bindParam(':password', $hashed_password);

                if ($insert->execute()) {
                    $success = true;
                } else {
                    $errors[] = "Error al registrar el usuario. Inténtalo más tarde.";
                }
            }
        } catch (PDOException $e) {
            error_log("Error en register.php: " . $e->getMessage(), 3, __DIR__ . '/errors.log');
            $errors[] = "Ocurrió un problema con el servidor. Inténtalo más tarde.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: url('images/background.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #ffffff;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #app {
            width: 90%;
            max-width: 600px;
            text-align: center;
        }

        #register {
            background: rgba(0, 0, 0, 0.85);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.6);
        }

        header h1 {
            color: #ffa500;
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .step {
            margin-bottom: 15px;
            text-align: left;
        }

        label {
            display: block;
            font-weight: bold;
            color: #ffa500;
        }

        input {
            padding: 10px;
            width: 100%;
            border-radius: 5px;
            border: none;
            margin-top: 5px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
        }

        input.valid {
            border: 2px solid #2ecc40;
        }

        input.invalid {
            border: 2px solid #ff4136;
        }

        .requirement {
            font-size: 0.8rem;
            color: #ccc;
            margin-top: 5px;
        }

        .requirement.valid {
            color: #2ecc40;
        }

        .requirement.invalid {
            color: #ff4136;
        }

        button {
            padding: 10px;
            width: 100%;
            border-radius: 10px;
            background: linear-gradient(45deg, #ffa500, #ff7500);
            color: #fff;
            font-size: 1.2rem;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: linear-gradient(45deg, #ff8c00, #ff6600);
        }

        button:disabled {
            background: gray;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div id="app">
        <div id="register">
            <header><h1>🌟 Crear una Cuenta 🌟</h1></header>
            <?php if (!empty($errors)): ?>
                <div style="color:#ff4136"><?= implode('<br>', $errors) ?></div>
            <?php elseif ($success): ?>
                <div style="color:#2ecc40">¡Registro exitoso! Redirigiendo...</div>
                <script>setTimeout(() => window.location.href = "index.php", 3000);</script>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="step">
                    <label for="regUsername">Nombre de Usuario:</label>
                    <input type="text" name="regUsername" id="regUsername" required>
                    <div id="usernameReq" class="requirement">Debe tener entre 3 y 20 caracteres y solo letras, números o guiones bajos.</div>
                </div>
                <div class="step">
                    <label for="regEmail">Email:</label>
                    <input type="email" name="regEmail" id="regEmail" required>
                    <div id="emailReq" class="requirement">Debe ser un correo electrónico válido.</div>
                </div>
                <div class="step">
                    <label for="regPassword">Contraseña:</label>
                    <input type="password" name="regPassword" id="regPassword" required>
                    <div id="passwordReq" class="requirement">Al menos 8 caracteres, una letra mayúscula, una minúscula y un número.</div>
                </div>
                <button type="submit" id="submitButton" disabled>Registrar</button>
            </form>
        </div>
    </div>

    <script>
        const username = document.getElementById('regUsername');
        const email = document.getElementById('regEmail');
        const password = document.getElementById('regPassword');
        const submitButton = document.getElementById('submitButton');

        const usernameReq = document.getElementById('usernameReq');
        const emailReq = document.getElementById('emailReq');
        const passwordReq = document.getElementById('passwordReq');

        function validateField(field, regex, reqText) {
            if (regex.test(field.value)) {
                field.classList.add('valid');
                field.classList.remove('invalid');
                reqText.classList.add('valid');
                reqText.classList.remove('invalid');
            } else {
                field.classList.add('invalid');
                field.classList.remove('valid');
                reqText.classList.add('invalid');
                reqText.classList.remove('valid');
            }
            checkFormValidity();
        }

        function checkFormValidity() {
            const allValid = username.classList.contains('valid') &&
                             email.classList.contains('valid') &&
                             password.classList.contains('valid');
            submitButton.disabled = !allValid;
        }

        username.addEventListener('input', () => validateField(username, /^[a-zA-Z0-9_]{3,20}$/, usernameReq));
        email.addEventListener('input', () => validateField(email, /^[^\s@]+@[^\s@]+\.[^\s@]+$/, emailReq));
        password.addEventListener('input', () => validateField(password, /(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}/, passwordReq));
    </script>
</body>
</html>
