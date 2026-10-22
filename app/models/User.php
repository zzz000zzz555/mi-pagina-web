<?php
require_once __DIR__ . '/../libs/AuditLog.php';

class User {
    private $conn;
    private $table = "usuarios";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. LEER TODOS LOS USUARIOS
    public function readAll() {
        $query = "SELECT id, nombre_usuario AS username, contrasena AS password, rol AS role,
                         creado_en AS created_at, estado AS status
                  FROM " . $this->table . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. OBTENER UN USUARIO (Para editar)
    public function getById($id) {
        $query = "SELECT id, nombre_usuario AS username, contrasena AS password, rol AS role,
                         creado_en AS created_at, estado AS status
                  FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 3. CREAR USUARIO (Con status 'activo' por defecto)
    public function create($username, $password, $role) {
        // Verificar si el usuario ya existe para evitar duplicados
        $check = "SELECT id FROM " . $this->table . " WHERE nombre_usuario = :u";
        $stmtCheck = $this->conn->prepare($check);
        $stmtCheck->bindParam(':u', $username);
        $stmtCheck->execute();
        
        if($stmtCheck->rowCount() > 0) {
            return false; // El usuario ya existe
        }

        // Encriptar contraseña
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertar
        $query = "INSERT INTO " . $this->table . " (nombre_usuario, contrasena, rol, estado) VALUES (:u, :p, :r, 'activo')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':u', $username);
        $stmt->bindParam(':p', $hash);
        $stmt->bindParam(':r', $role);
        $saved = $stmt->execute();
        if ($saved) {
            AuditLog::record($this->conn, 'crear', 'usuario', (int) $this->conn->lastInsertId(), 'Se creó el usuario ' . $username . '.', ['after' => ['username' => $username, 'role' => $role, 'status' => 'activo']]);
        }
        return $saved;
    }

    // 4. ACTUALIZAR USUARIO
    public function update($id, $username, $role, $password = null) {
        $before = $this->getById($id);
        if (!empty($password)) {
            // Si escribieron contraseña, la actualizamos encriptada
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE " . $this->table . " SET nombre_usuario = :u, rol = :r, contrasena = :p WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':p', $hash);
        } else {
            // Si no, solo actualizamos datos básicos
            $query = "UPDATE " . $this->table . " SET nombre_usuario = :u, rol = :r WHERE id = :id";
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->bindParam(':u', $username);
        $stmt->bindParam(':r', $role);
        $stmt->bindParam(':id', $id);
        $saved = $stmt->execute();
        if ($saved && $before) {
            AuditLog::record($this->conn, 'editar', 'usuario', (int) $id, 'Se actualizaron los datos del usuario ' . $username . '.', [
                'before' => ['username' => $before['username'], 'role' => $before['role']],
                'after' => ['username' => $username, 'role' => $role, 'password_changed' => !empty($password)]
            ]);
        }
        return $saved;
    }

    // 5. CAMBIAR ESTADO (ACTIVAR/DESACTIVAR)
    public function toggleStatus($id, $newStatus) {
        $before = $this->getById($id);
        $query = "UPDATE " . $this->table . " SET estado = :s WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':s', $newStatus);
        $stmt->bindParam(':id', $id);
        $saved = $stmt->execute();
        if ($saved && $before) {
            AuditLog::record($this->conn, 'cambiar_estado', 'usuario', (int) $id, 'Se cambió el estado del usuario ' . $before['username'] . '.', [
                'before' => ['status' => $before['status']],
                'after' => ['status' => $newStatus]
            ]);
        }
        return $saved;
    }

    // 6. CAMBIAR SOLO CONTRASEÑA (Para la función "Cambiar mi clave")
    public function updatePassword($id, $newHash) {
        $query = "UPDATE " . $this->table . " SET contrasena = :p WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':p', $newHash);
        $stmt->bindParam(':id', $id);
        $saved = $stmt->execute();
        if ($saved) {
            AuditLog::record($this->conn, 'cambiar_clave', 'usuario', (int) $id, 'Se cambió la contraseña del usuario.', []);
        }
        return $saved;
    }

    // 7. ELIMINAR (Opcional, ya que ahora usamos desactivar)
    public function delete($id) {
        $before = $this->getById($id);
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $deleted = $stmt->execute();
        if ($deleted && $stmt->rowCount() > 0 && $before) {
            AuditLog::record($this->conn, 'eliminar', 'usuario', (int) $id, 'Se eliminó el usuario ' . $before['username'] . '.', ['before' => ['username' => $before['username'], 'role' => $before['role']]]);
        }
        return $deleted;
    }
}
?>