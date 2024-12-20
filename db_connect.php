<?php

// Usamos una clase Singleton para encapsular la conexión a la base de datos usando PDO
class DatabaseConnection {
    private $connection;
    private static $instance;

    private function __construct() {
        $servername = 'localhost';
        $dbname = 'u160479176_rpgminedb';

        // Credenciales usando variables de entorno o valores predeterminados
        $username = getenv('DB_USERNAME') ?: 'u160479176_Wyzardy';
        $password = getenv('DB_PASSWORD') ?: 'Co13311096@';

        try {
            // Crear una conexión PDO
            $this->connection = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);

            // Configurar PDO para lanzar excepciones en caso de error
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->logError("Error de conexión: " . $e->getMessage());
            throw new Exception("No se pudo conectar a la base de datos.");
        }
    }

    // Patrón Singleton para asegurar una única instancia de conexión
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Método para obtener la conexión PDO
    public function getConnection() {
        return $this->connection;
    }

    // Registrar errores en un archivo de log
    private function logError($message) {
        error_log($message, 3, __DIR__ . '/db_errors.log');
    }
}

// Uso de la conexión
try {
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection(); // $conn es tu objeto PDO
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>
