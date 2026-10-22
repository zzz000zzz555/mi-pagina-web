<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function getConnection() {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'control_acceso_db';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            
            // 2. SINCRONIZAR ZONA HORARIA CON PHP
            // date("P") devuelve la diferencia horaria configurada en index.php (ej: "-05:00")
            // Esto obliga a MySQL a guardar la hora exacta de Perú.
            $offset = date("P"); 
            $this->conn->exec("SET time_zone='$offset';");

        } catch(PDOException $exception) {
            error_log('Database connection failed: ' . $exception->getMessage());
            http_response_code(500);
            exit('No fue posible conectar con el sistema. Comuníquese con el administrador.');
        }
        return $this->conn;
    }
}
?>
