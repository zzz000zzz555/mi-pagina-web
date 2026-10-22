<?php
require_once __DIR__ . '/../libs/AuditLog.php';

class Department {
    private $conn;
    private $table = "especialidades";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. LEER TODOS
    public function readAll() {
        $query = "SELECT id, nombre AS name, estado AS status FROM " . $this->table . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. CREAR
    public function create($name) {
        // Ahora incluimos el status por defecto
        $query = "INSERT INTO " . $this->table . " (nombre, estado) VALUES (:name, 'activo')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $saved = $stmt->execute();
        if ($saved) {
            AuditLog::record($this->conn, 'crear', 'especialidad', (int) $this->conn->lastInsertId(), 'Se creó la especialidad ' . $name . '.', ['after' => ['name' => $name, 'status' => 'activo']]);
        }
        return $saved;
    }

    // 3. OBTENER UNO
    public function getById($id) {
        $query = "SELECT id, nombre AS name, estado AS status FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 4. ACTUALIZAR NOMBRE
    public function update($id, $name) {
        $before = $this->getById($id);
        $query = "UPDATE " . $this->table . " SET nombre = :name WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':id', $id);
        $saved = $stmt->execute();
        if ($saved && $before) {
            AuditLog::record($this->conn, 'editar', 'especialidad', (int) $id, 'Se actualizó la especialidad ' . $name . '.', [
                'before' => ['name' => $before['name']],
                'after' => ['name' => $name]
            ]);
        }
        return $saved;
    }

    // 5. CAMBIAR ESTADO (Nuevo)
    public function toggleStatus($id, $newStatus) {
        $before = $this->getById($id);
        $query = "UPDATE " . $this->table . " SET estado = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $newStatus);
        $stmt->bindParam(':id', $id);
        $saved = $stmt->execute();
        if ($saved && $before) {
            AuditLog::record($this->conn, 'cambiar_estado', 'especialidad', (int) $id, 'Se cambió el estado de la especialidad ' . $before['name'] . '.', [
                'before' => ['status' => $before['status']],
                'after' => ['status' => $newStatus]
            ]);
        }
        return $saved;
    }

    // 6. ELIMINAR
    public function delete($id) {
        $before = $this->getById($id);
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $deleted = $stmt->execute();
        if ($deleted && $stmt->rowCount() > 0 && $before) {
            AuditLog::record($this->conn, 'eliminar', 'especialidad', (int) $id, 'Se eliminó la especialidad ' . $before['name'] . '.', ['before' => ['name' => $before['name'], 'status' => $before['status']]]);
        }
        return $deleted;
    }
}
?>