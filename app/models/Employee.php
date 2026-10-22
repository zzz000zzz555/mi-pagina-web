<?php
require_once __DIR__ . '/../libs/AuditLog.php';

class Employee {
    private $conn;
    private $table = "estudiantes";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. LEER TODOS (Para Admin)
    public function read($filters = []) {
        $query = "SELECT e.id, e.codigo_estudiante AS employee_code, e.nombres AS first_name,
                         e.apellidos AS last_name, e.dni, e.edad AS age, e.sexo AS sex,
                         e.correo AS email, e.contrasena AS password,
                         e.especialidad_id AS department_id, e.ciclo_id AS cycle_id,
                         e.semestre_id AS semester_id, e.ciclo_legado AS legacy_cycle,
                         e.turno AS shift, e.ruta_foto AS photo_path, e.estado AS status,
                         e.creado_en AS created_at, d.nombre AS department_name,
                         c.nombre AS cycle_name, s.nombre AS semester_name
                  FROM " . $this->table . " e
                  LEFT JOIN especialidades d ON e.especialidad_id = d.id
                  LEFT JOIN ciclos c ON e.ciclo_id = c.id
                  LEFT JOIN semestres s ON e.semestre_id = s.id";

        // Compatibilidad con la búsqueda general usada por otras vistas.
        if (is_string($filters)) {
            $filters = ['search' => trim($filters)];
        }

        $conditions = [];
        $params = [];
        $likeFields = [
            'cycle' => 'c.nombre'
        ];

        foreach ($likeFields as $filter => $column) {
            if (!empty($filters[$filter])) {
                $conditions[] = $column . ' LIKE :' . $filter;
                $params[$filter] = '%' . trim($filters[$filter]) . '%';
            }
        }

        if (!empty($filters['department_id'])) {
            $conditions[] = 'e.especialidad_id = :department_id';
            $params['department_id'] = (int) $filters['department_id'];
        }

        if (!empty($filters['shift']) && in_array($filters['shift'], ['manana', 'tarde'], true)) {
            $conditions[] = 'e.turno = :shift';
            $params['shift'] = $filters['shift'];
        }

        if (!empty($filters['cycle_id']) && filter_var($filters['cycle_id'], FILTER_VALIDATE_INT) !== false) {
            $conditions[] = 'e.ciclo_id = :cycle_id';
            $params['cycle_id'] = (int) $filters['cycle_id'];
        }

        if (!empty($filters['semester_id']) && filter_var($filters['semester_id'], FILTER_VALIDATE_INT) !== false) {
            $conditions[] = 'e.semestre_id = :semester_id';
            $params['semester_id'] = (int) $filters['semester_id'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(CONCAT(e.nombres, ' ', e.apellidos) LIKE :search OR e.dni LIKE :search)";
            $params['search'] = '%' . trim($filters['search']) . '%';
        }

        if ($conditions) $query .= ' WHERE ' . implode(' AND ', $conditions);
        $query .= " ORDER BY e.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. OBTENER UNO
    public function getById($id) {
        $query = "SELECT e.id, e.codigo_estudiante AS employee_code, e.nombres AS first_name,
                         e.apellidos AS last_name, e.dni, e.edad AS age, e.sexo AS sex,
                         e.correo AS email, e.contrasena AS password,
                         e.especialidad_id AS department_id, e.ciclo_id AS cycle_id,
                         e.semestre_id AS semester_id, e.ciclo_legado AS legacy_cycle,
                         e.turno AS shift, e.ruta_foto AS photo_path, e.estado AS status,
                         e.creado_en AS created_at, c.nombre AS cycle_name, s.nombre AS semester_name
                  FROM " . $this->table . " e
                  LEFT JOIN ciclos c ON e.ciclo_id = c.id
                  LEFT JOIN semestres s ON e.semestre_id = s.id
                  WHERE e.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByIds($ids) {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids), function ($id) {
            return $id > 0;
        })));
        if (!$ids) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT e.id, e.codigo_estudiante AS employee_code, e.nombres AS first_name,
                         e.apellidos AS last_name, e.dni, e.edad AS age, e.sexo AS sex,
                         e.correo AS email, e.contrasena AS password,
                         e.especialidad_id AS department_id, e.ciclo_id AS cycle_id,
                         e.semestre_id AS semester_id, e.ciclo_legado AS legacy_cycle,
                         e.turno AS shift, e.ruta_foto AS photo_path, e.estado AS status,
                         e.creado_en AS created_at, d.nombre AS department_name,
                         c.nombre AS cycle_name, s.nombre AS semester_name
                  FROM " . $this->table . " e
                  LEFT JOIN especialidades d ON e.especialidad_id = d.id
                  LEFT JOIN ciclos c ON e.ciclo_id = c.id
                  LEFT JOIN semestres s ON e.semestre_id = s.id
                  WHERE e.id IN ($placeholders)
                  ORDER BY e.apellidos, e.nombres";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasAcademicAssignment($cycleId, $semesterId) {
        $stmt = $this->conn->prepare(
            "SELECT
                EXISTS(SELECT 1 FROM ciclos WHERE id = :cycle_id) AS valid_cycle,
                EXISTS(SELECT 1 FROM semestres WHERE id = :semester_id) AS valid_semester"
        );
        $stmt->execute([
            'cycle_id' => (int) $cycleId,
            'semester_id' => (int) $semesterId
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && (int) $result['valid_cycle'] === 1 && (int) $result['valid_semester'] === 1;
    }

    public function existsByDni($dni) {
        $stmt = $this->conn->prepare("SELECT 1 FROM " . $this->table . " WHERE dni = :dni LIMIT 1");
        $stmt->execute(['dni' => $dni]);
        return (bool) $stmt->fetchColumn();
    }

    // 3. CREAR (Con contraseña por defecto '123456')
    public function create($data) {
        $defaultPass = password_hash("123456", PASSWORD_DEFAULT);
        $query = "INSERT INTO " . $this->table . "
                 (codigo_estudiante, nombres, apellidos, dni, edad, sexo, correo, contrasena, especialidad_id, ciclo_id, semestre_id, turno, ruta_foto, estado)
                 VALUES (:code, :fname, :lname, :dni, :age, :sex, :email, :pass, :dept, :cycle_id, :semester_id, :shift, :photo, 'activo')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $data['employee_code']);
        $stmt->bindParam(':fname', $data['first_name']);
        $stmt->bindParam(':lname', $data['last_name']);
        $stmt->bindParam(':dni', $data['dni']);
        $stmt->bindParam(':age', $data['age'], PDO::PARAM_INT);
        $stmt->bindParam(':sex', $data['sex']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':pass', $defaultPass);
        $stmt->bindParam(':dept', $data['department_id']);
        $stmt->bindParam(':cycle_id', $data['cycle_id'], PDO::PARAM_INT);
        $stmt->bindParam(':semester_id', $data['semester_id'], PDO::PARAM_INT);
        $stmt->bindParam(':shift', $data['shift']);
        $stmt->bindParam(':photo', $data['photo_path']);
        $saved = $stmt->execute();
        if ($saved) {
            AuditLog::record($this->conn, 'crear', 'estudiante', (int) $this->conn->lastInsertId(), 'Se registró el estudiante ' . $data['first_name'] . ' ' . $data['last_name'] . '.', [
                'after' => $this->auditSnapshot($data)
            ]);
        }
        return $saved;
    }

    // 4. ACTUALIZAR DATOS
    public function update($data) {
        $query = "UPDATE " . $this->table . " 
                  SET nombres = :fname, apellidos = :lname, 
                      dni = :dni, edad = :age, sexo = :sex, correo = :email,
                      especialidad_id = :dept, ciclo_id = :cycle_id, semestre_id = :semester_id,
                      ciclo_legado = NULL, turno = :shift,
                      ruta_foto = :photo
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fname', $data['first_name']);
        $stmt->bindParam(':lname', $data['last_name']);
        $stmt->bindParam(':dni', $data['dni']);
        $stmt->bindParam(':age', $data['age'], PDO::PARAM_INT);
        $stmt->bindParam(':sex', $data['sex']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':dept', $data['department_id']);
        $stmt->bindParam(':cycle_id', $data['cycle_id'], PDO::PARAM_INT);
        $stmt->bindParam(':semester_id', $data['semester_id'], PDO::PARAM_INT);
        $stmt->bindParam(':shift', $data['shift']);
        $stmt->bindParam(':photo', $data['photo_path']);
        $stmt->bindParam(':id', $data['id']);
        $before = $this->getById($data['id']);
        $saved = $stmt->execute();
        if ($saved && $before) {
            AuditLog::record($this->conn, 'editar', 'estudiante', (int) $data['id'], 'Se actualizaron los datos de ' . $data['first_name'] . ' ' . $data['last_name'] . '.', [
                'before' => $this->auditSnapshot($before),
                'after' => $this->auditSnapshot($data)
            ]);
        }
        return $saved;
    }

    public function generateUniqueCode() {
        $prefix = 'JSC-' . date('Y') . '-';
        $stmt = $this->conn->prepare(
            "SELECT codigo_estudiante FROM {$this->table} WHERE codigo_estudiante LIKE :prefix ORDER BY codigo_estudiante DESC LIMIT 1"
        );
        $like = $prefix . '%';
        $stmt->execute(['prefix' => $like]);
        $last = $stmt->fetchColumn();
        $next = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    // 5. CAMBIAR ESTADO
    public function toggleStatus($id, $newStatus) {
        $before = $this->getById($id);
        $query = "UPDATE " . $this->table . " SET estado = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $newStatus);
        $stmt->bindParam(':id', $id);
        $saved = $stmt->execute();
        if ($saved && $before) {
            AuditLog::record($this->conn, 'cambiar_estado', 'estudiante', (int) $id, 'Se cambió el estado del estudiante ' . $before['first_name'] . ' ' . $before['last_name'] . '.', [
                'before' => ['status' => $before['status']],
                'after' => ['status' => $newStatus]
            ]);
        }
        return $saved;
    }

    // 6. ELIMINAR
    public function delete($id) {
        try {
            $before = $this->getById($id);
            $this->conn->beginTransaction();
            $stmtLogs = $this->conn->prepare("DELETE FROM registros_asistencia WHERE estudiante_id = :id");
            $stmtLogs->execute(['id' => (int) $id]);

            $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
            $stmt->execute(['id' => (int) $id]);
            $deleted = $stmt->rowCount() > 0;
            if ($deleted && $before) {
                AuditLog::record($this->conn, 'eliminar', 'estudiante', (int) $id, 'Se eliminó el estudiante ' . $before['first_name'] . ' ' . $before['last_name'] . '.', [
                    'before' => $this->auditSnapshot($before)
                ]);
            }
            $this->conn->commit();
            return $deleted;
        } catch (Throwable $error) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            return false;
        }
    }

    // 7. LOGIN EMPLEADO
    public function login($email, $password) {
        $query = "SELECT id, codigo_estudiante AS employee_code, nombres AS first_name,
                         apellidos AS last_name, dni, edad AS age, sexo AS sex,
                         correo AS email, contrasena AS password, especialidad_id AS department_id,
                         ciclo_id AS cycle_id, semestre_id AS semester_id, ciclo_legado AS legacy_cycle,
                         turno AS shift, ruta_foto AS photo_path, estado AS status, creado_en AS created_at
                  FROM " . $this->table . " WHERE correo = :email AND estado = 'activo' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Verificar si tiene password y si coincide
            if (!empty($row['password']) && password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    // 8. ACTUALIZAR SOLO CONTRASEÑA
    public function updatePassword($id, $newHash) {
        $query = "UPDATE " . $this->table . " SET contrasena = :pass WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pass', $newHash);
        $stmt->bindParam(':id', $id);
        $saved = $stmt->execute();
        if ($saved) {
            AuditLog::record($this->conn, 'cambiar_clave', 'estudiante', (int) $id, 'Se cambió la contraseña del estudiante.', []);
        }
        return $saved;
    }

    private function auditSnapshot($data) {
        $fields = ['employee_code', 'first_name', 'last_name', 'dni', 'age', 'sex', 'email', 'department_id', 'cycle_id', 'semester_id', 'shift', 'status'];
        $snapshot = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) $snapshot[$field] = $data[$field];
        }
        return $snapshot;
    }
}
?>
