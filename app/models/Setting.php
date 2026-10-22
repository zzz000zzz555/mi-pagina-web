<?php
require_once __DIR__ . '/../libs/AuditLog.php';

class Setting {
    private $conn;
    private $table = "configuracion";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener un valor por su nombre (Ej: 'entry_time')
    public function get($name) {
        $query = "SELECT valor_configuracion AS setting_value FROM " . $this->table . " WHERE nombre_configuracion = :name LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['setting_value'] : null;
    }

    // Guardar un valor
    public function set($name, $value) {
        $before = $this->get($name);
        $query = "INSERT INTO " . $this->table . " (nombre_configuracion, valor_configuracion)
                  VALUES (:name, :val)
                  ON DUPLICATE KEY UPDATE valor_configuracion = VALUES(valor_configuracion)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':val', $value);
        $stmt->bindParam(':name', $name);
        $saved = $stmt->execute();
        if ($saved && (string) $before !== (string) $value) {
            AuditLog::record($this->conn, 'editar', 'configuracion', null, 'Se actualizó el ajuste ' . $name . '.', [
                'before' => ['setting' => $name, 'value' => $before],
                'after' => ['setting' => $name, 'value' => $value]
            ]);
        }
        return $saved;
    }
}
?>
