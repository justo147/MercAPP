<?php

/**
 * Clase Database
 * 
 * Gestiona la conexión a la base de datos MySQL utilizando PDO.
 * Permite obtener un objeto PDO listo para realizar consultas.
 */
class Database {
    /**
     * @var string Host del servidor de base de datos
     */
    private $host = 'localhost';

    /**
     * @var string Nombre de la base de datos
     */
    private $db_name = 'mercapp';

    /**
     * @var string Usuario para la conexión a la base de datos
     */
    private $username = 'root';

    /**
     * @var string Contraseña para la conexión a la base de datos
     */
    private $password = '';

    /**
     * @var PDO|null Objeto de conexión PDO
     */
    public $conn;

    /**
     * Obtiene la conexión a la base de datos.
     *
     * @return PDO|null Devuelve un objeto PDO si la conexión es exitosa, o null si falla.
     */
    public function getConnection() {
        $this->conn = null;

        try {
            // Crear la conexión PDO con charset UTF-8
            // Esto asegura que los datos con acentos y caracteres especiales se manejen correctamente
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8", 
                $this->username, 
                $this->password
            );

            // Configurar PDO para que lance excepciones en caso de error
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch(PDOException $exception) {
            // Mostrar error en desarrollo. En producción, se recomienda usar error_log() en lugar de echo.
            echo "Error de conexión: " . $exception->getMessage();
            // Ejemplo para producción:
            // error_log("Connection error: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
