<?php

/**
 * Genera un token CSRF único para cada sesión.
 * 
 * @return string Token CSRF generado.
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida el token CSRF enviado contra el token de la sesión.
 * 
 * @param string $token Token CSRF a validar.
 * @return bool True si el token es válido, false en caso contrario.
 */
function validateCSRFToken($token) {
    return hash_equals($token, $_SESSION['csrf_token'] ?? '');
}

/**
 * Verifica si un usuario está autenticado.
 * 
 * @return bool True si el usuario está autenticado, false en caso contrario.
 */
function checkAuth() {
    return isset($_SESSION['user_id']) && isset($_SESSION['character_id']);
}

/**
 * Sanitiza y valida una entrada de texto.
 * 
 * @param string $input El texto a sanitizar.
 * @return string El texto sanitizado.
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitiza y valida un array de datos de entrada.
 * 
 * @param array $data El array de datos a sanitizar.
 * @return array El array con los datos sanitizados.
 */
function sanitizeArray($data) {
    if (!is_array($data)) {
        return sanitizeInput($data);
    }
    $sanitized = [];
    foreach ($data as $key => $value) {
        $sanitized[$key] = is_array($value) ? sanitizeArray($value) : sanitizeInput($value);
    }
    return $sanitized;
}

/**
 * Limpia y valida una entrada numérica.
 * 
 * @param mixed $input El dato a validar como número.
 * @return int|float|bool El número validado o false si no es válido.
 */
function validateNumber($input) {
    if (is_numeric($input)) {
        return is_float($input + 0) ? (float)$input : (int)$input;
    }
    return false;
}

/**
 * Escapa una cadena para ser usada en una consulta SQL para evitar inyecciones SQL.
 * 
 * @param PDO $conn La conexión PDO.
 * @param string $string La cadena a escapar.
 * @return string La cadena escapada.
 */
function escapeSQL($conn, $string) {
    return $conn->quote($string);
}

/**
 * Valida un email usando una expresión regular.
 * 
 * @param string $email El email a validar.
 * @return bool True si el email es válido, false en caso contrario.
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida una contraseña según criterios específicos (aquí puedes ajustar los criterios).
 * 
 * @param string $password La contraseña a validar.
 * @return bool True si la contraseña cumple con los criterios, false en caso contrario.
 */
function validatePassword($password) {
    $length = strlen($password);
    if ($length < 8 || $length > 72) {
        return false;
    }
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
}

/**
 * Genera un hash seguro para la contraseña.
 * 
 * @param string $password La contraseña a hashear.
 * @return string El hash de la contraseña.
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verifica si una contraseña coincide con un hash.
 * 
 * @param string $password La contraseña a verificar.
 * @param string $hash El hash contra el cual verificar.
 * @return bool True si la contraseña coincide con el hash, false en caso contrario.
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Estas funciones pueden expandirse o modificarse según las necesidades específicas de tu aplicación.