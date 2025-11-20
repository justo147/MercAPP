<?php

class Database {
    private $host = 'localhost';
    private $db_name = 'mercapp';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // En producción, no deberíamos mostrar el error exacto al usuario, sino loguearlo.
            // error_log("Connection error: " . $exception->getMessage());
            echo "Error de conexión: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
