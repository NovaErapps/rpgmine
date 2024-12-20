<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPG Login</title>
    <style>
        /* Estilos básicos */
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #ffffff;
            height: 100vh;
            overflow: hidden;
            background: #000; /* Fondo oscuro para mejor contraste con el video */
        }

        #backgroundVideo {
            position: fixed;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            z-index: -100;
            transform: translateX(-50%) translateY(-50%);
            filter: brightness(0.5); /* Hace el video más oscuro para mejorar la legibilidad */
        }

        #app {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            max-width: 500px;
            padding: 40px;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 20px;
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.6);
            animation: fadeInUp 1s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate(-50%, 20px);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        #login header img {
            width: 50%; /* Hacer el logo más pequeño */
            margin: 20px auto 30px auto; /* Centrar el logo y ajustar márgenes */
            display: block; /* Asegurar que la imagen sea un bloque para centrarla */
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { filter: drop-shadow(0 0 10px #ff8c00); }
            to { filter: drop-shadow(0 0 20px #ff8c00); }
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        input {
            padding: 15px;
            border-radius: 30px;
            border: 2px solid #ff8c00;
            font-size: 1rem;
            width: 100%;
            background: transparent;
            color: #ffffff;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #ff6600;
            box-shadow: 0 0 10px #ff6600;
        }

        input::placeholder {
            color: #aaaaaa;
        }

        button {
            padding: 15px;
            border-radius: 30px;
            border: none;
            font-size: 1.2rem;
            background: linear-gradient(45deg, #ff8c00, #ff6600);
            color: #ffffff;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        button:hover {
            transform: scale(1.05);
            background: linear-gradient(45deg, #ff7500, #ff4500);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5);
        }

        .new-user a {
            color: #ff8c00;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }

        .new-user a:hover {
            color: #ff6600;
            text-decoration: underline;
        }

        .new-user {
            margin-top: 10px;
            font-size: 1rem;
            text-align: center; /* Centrar el texto */
        }

        p.error-message {
            color: #ff4500;
            margin-top: 10px;
            font-size: 0.9rem;
            animation: shake 0.5s;
            text-align: center; /* Centrar el mensaje de error */
        }
            @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
        20%, 40%, 60%, 80% { transform: translateX(10px); }
    }

    #unmuteButton {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: transparent;
        color: #ff8c00;
        border: 2px solid #ff8c00;
        padding: 10px 20px;
        border-radius: 30px;
        font-size: 1rem;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        display: none;
    }

    #unmuteButton:hover {
        background: #ff8c00;
        color: #fff;
    }
</style>
</head>
<body>
    <video autoplay loop muted id="backgroundVideo">
        <source src="https://rpgmine.net/images/Intro.mp4" type="video/mp4">
        Tu navegador no soporta el elemento de video.
    </video>
    <button id="unmuteButton">Activar sonido</button>

<div id="app">
    <div id="login">
        <header>
            <img src="/images/rpgimelogo.png" alt="RPG Mine Logo">
        </header>
        <form id="loginForm" action="login.php" method="POST">
            <div class="step">
                <label for="username">Nombre de Usuario o Email:</label>
                <input type="text" name="username" id="username" placeholder="Ingresa tu usuario o email" required>
            </div>
            <div class="step">
                <label for="password">Contraseña:</label>
                <input type="password" name="password" id="password" placeholder="Ingresa tu contraseña" required>
            </div>
            <button type="submit">Iniciar Sesión</button>
            <p class="new-user">¿No tienes cuenta? <a href="register.php">Crear una cuenta</a></p>
        </form>
        <?php
        if (isset($_GET['error']) && $_GET['error'] == 'login') {
            echo "<p class='error-message'>Credenciales incorrectas.</p>";
        }
        ?>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const video = document.getElementById("backgroundVideo");
        const unmuteButton = document.getElementById("unmuteButton");

        // Mostrar el botón si el video está silenciado
        unmuteButton.style.display = "block";

        unmuteButton.addEventListener("click", () => {
            video.muted = false;
            video.play().catch((error) => {
                console.error("Error al reproducir el video:", error);
            });
            unmuteButton.style.display = "none"; // Ocultar el botón después de activar el sonido
        });
    });
</script>
</body>
</html>