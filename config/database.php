<?php
/**
 * Configuración de Base de Datos para Sistema POS
 * Sistema de Punto de Venta para Abarrotes
 * 
 * IMPORTANTE: Colocar este archivo en: config/database.php
 */

class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "grocerystore";
    private $connection;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $this->connection = new mysqli(
                $this->host, 
                $this->username, 
                $this->password, 
                $this->database
            );
            
            if ($this->connection->connect_error) {
                throw new Exception("Error de conexión: " . $this->connection->connect_error);
            }
            
            // Configurar charset UTF-8
            $this->connection->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            die("Error al conectar con la base de datos: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql) {
        $result = $this->connection->query($sql);
        if (!$result) {
            throw new Exception("Error en consulta: " . $this->connection->error);
        }
        return $result;
    }
    
    public function prepare($sql) {
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $this->connection->error);
        }
        return $stmt;
    }
    
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
    
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    public function __destruct() {
        $this->close();
    }
}

// Instancia global de la base de datos
$db = new Database();
$conn = $db->getConnection();
?>