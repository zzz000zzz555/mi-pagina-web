<?php
class Attendance {
    private $conn;
    private $table = "registros_asistencia";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. OBTENER LOS ÚLTIMOS 10 REGISTROS (Para tabla de recientes)
    public function getRecentLogs() {
        $query = "SELECT a.id, a.estudiante_id AS employee_id, a.fecha AS date_log,
                         a.hora_entrada AS check_in_time, a.hora_salida AS check_out_time,
                         a.horas_totales AS total_hours, a.estado AS status,
                         e.nombres AS first_name, e.apellidos AS last_name,
                         e.codigo_estudiante AS employee_code
                  FROM " . $this->table . " a
                  INNER JOIN estudiantes e ON a.estudiante_id = e.id
                  ORDER BY a.id DESC LIMIT 10";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. CONTAR CUÁNTOS HAN MARCADO HOY (Para tarjeta verde)
    public function countToday() {
        $today = date('Y-m-d');
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE fecha = :today";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // 3. OBTENER HISTORIAL POR RANGO DE FECHAS (Para Reporte Excel)
    public function getHistoryByDate($start, $end, $filters = []) {
        return $this->getLogsWithFilters($filters, $start, $end);
    }

    // 4. ESTADÍSTICAS ÚLTIMOS 7 DÍAS (Para el Gráfico)
    public function getWeeklyStats() {
        $query = "SELECT fecha AS date_log, COUNT(*) as total 
                  FROM " . $this->table . " 
                  GROUP BY fecha 
                  ORDER BY fecha DESC 
                  LIMIT 7";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 5. HISTORIAL CON FILTROS (Para Pantalla Historial)
    public function getLogsWithFilters($filters, $start, $end, $limit = null, $offset = 0) {
        if (!is_array($filters)) $filters = ['employee_id' => $filters];
        $query = "SELECT a.id AS attendance_id, a.fecha AS date_log, a.hora_entrada AS check_in_time,
                         a.hora_salida AS check_out_time, a.horas_totales AS total_hours, a.estado AS status,
                         e.id AS employee_id, e.nombres AS first_name,
                         e.apellidos AS last_name, e.codigo_estudiante AS employee_code,
                         e.dni, COALESCE(c.nombre, e.ciclo_legado, 'Sin asignar') AS cycle_name,
                         s.nombre AS semester_name, d.nombre AS department, e.turno AS shift,
                         (SELECT GROUP_CONCAT(CONCAT(DATE_FORMAT(m.fecha_hora, '%H:%i'), ' · ', REPLACE(m.tipo, '_', ' ')) ORDER BY m.fecha_hora, m.id SEPARATOR ' | ')
                          FROM movimientos_asistencia m WHERE m.registro_asistencia_id = a.id) AS movimientos,
                         (SELECT GROUP_CONCAT(m.motivo ORDER BY m.fecha_hora, m.id SEPARATOR ' | ')
                          FROM movimientos_asistencia m WHERE m.registro_asistencia_id = a.id AND m.motivo IS NOT NULL AND m.motivo <> '') AS motivos_salida
                  FROM " . $this->table . " a
                  INNER JOIN estudiantes e ON a.estudiante_id = e.id
                  LEFT JOIN ciclos c ON e.ciclo_id = c.id
                  LEFT JOIN semestres s ON e.semestre_id = s.id
                  LEFT JOIN especialidades d ON e.especialidad_id = d.id
                  WHERE a.fecha BETWEEN :start AND :end";

        $params = ['start' => $start, 'end' => $end];
        $likeFields = [];
        foreach ($likeFields as $filter => $column) {
            if (!empty($filters[$filter])) {
                $query .= " AND $column LIKE :$filter";
                $params[$filter] = '%' . trim($filters[$filter]) . '%';
            }
        }
        if (!empty($filters['search'])) {
            $query .= " AND (CONCAT(e.nombres, ' ', e.apellidos) LIKE :search_name OR e.dni LIKE :search_dni)";
            $search = '%' . trim($filters['search']) . '%';
            $params['search_name'] = $search;
            $params['search_dni'] = $search;
        }
        if (!empty($filters['employee_id'])) {
            $query .= " AND e.id = :employee_id";
            $params['employee_id'] = (int) $filters['employee_id'];
        }
        if (!empty($filters['department_id'])) {
            $query .= " AND e.especialidad_id = :department_id";
            $params['department_id'] = (int) $filters['department_id'];
        }
        if (!empty($filters['cycle_id']) && filter_var($filters['cycle_id'], FILTER_VALIDATE_INT) !== false) {
            $query .= " AND e.ciclo_id = :cycle_id";
            $params['cycle_id'] = (int) $filters['cycle_id'];
        }
        if (!empty($filters['semester_id']) && filter_var($filters['semester_id'], FILTER_VALIDATE_INT) !== false) {
            $query .= " AND e.semestre_id = :semester_id";
            $params['semester_id'] = (int) $filters['semester_id'];
        }
        if (!empty($filters['shift']) && in_array($filters['shift'], ['manana', 'tarde'], true)) {
            $query .= " AND e.turno = :shift";
            $params['shift'] = $filters['shift'];
        }
        $query .= " ORDER BY a.fecha DESC, a.hora_entrada DESC";
        if ($limit !== null) {
            $query .= " LIMIT " . max(1, (int) $limit) . " OFFSET " . max(0, (int) $offset);
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 6. CONTAR TARDANZAS DE HOY (NUEVO PARA DASHBOARD)
    public function countLatesToday($horaLimite = null) {
        $today = date('Y-m-d');
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table . " 
                  WHERE fecha = :today AND estado = 'tarde'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>
