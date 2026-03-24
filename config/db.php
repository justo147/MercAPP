<?php
require_once 'bootstrap.php';
class Database {

    private $host;
    private $db_name;
    private $username;
    private $password;

    public $conn;

    public function __construct() {
        // Cargar valores desde .env
        $this->host     = $_ENV["DB_HOST"] ?? 'localhost';
        $this->db_name  = $_ENV["DB_NAME"];
        $this->username = $_ENV["DB_USERNAME"];
        $this->password = $_ENV["DB_PASS"];
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8",
                $this->username,
                $this->password
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
