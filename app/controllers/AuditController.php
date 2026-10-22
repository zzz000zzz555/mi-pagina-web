<?php
require_once '../app/config/db.php';

class AuditController {
    private $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: ?c=Dashboard');
            exit;
        }
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function index() {
        $filters = $this->filters($_GET);
        $rangeError = !$this->validDateRange($filters['start'], $filters['end']);
        if ($rangeError) {
            $filters['start'] = date('Y-m-01');
            $filters['end'] = date('Y-m-d');
        }

        $page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
        $perPage = 50;
        $total = $this->countLogs($filters);
        $pageCount = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pageCount);
        $logs = $this->getLogs($filters, $perPage, ($page - 1) * $perPage);
        require_once '../app/views/audit/index.php';
    }

    public function export() {
        $filters = $this->filters($_GET);
        if (!$this->validDateRange($filters['start'], $filters['end'])) {
            header('Location: ?c=Audit&err=rango_fechas');
            exit;
        }
        $logs = $this->getLogs($filters);
        $escape = static function ($value) {
            $value = (string) $value;
            if (preg_match('/^[=+\-@]/', $value)) $value = "'" . $value;
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="Movimientos_' . date('Ymd_His') . '.xls"');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        echo "\xEF\xBB\xBF";
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
            body{font-family:Calibri,Arial,sans-serif;color:#243447}
            .report{width:100%;border-collapse:collapse;table-layout:fixed}
            .title{background:#102f4a;color:#fff;font-size:18pt;font-weight:bold;padding:18px 16px}
            .subtitle{background:#eaf3f6;color:#486376;padding:9px 12px}
            .summary{background:#f7fafc;color:#486376;padding:8px 12px;font-weight:bold}
            .headers th{background:#176378;color:#fff;padding:10px 8px;border:1px solid #125266;text-align:left}
            .data td{padding:8px;border:1px solid #d8e5ea;vertical-align:top;word-wrap:break-word}
            .alt td{background:#f4f9fa}.text{mso-number-format:"\\@"}.center{text-align:center}
        </style></head><body><table class="report">
        <colgroup><col style="width:15%"><col style="width:13%"><col style="width:13%"><col style="width:13%"><col style="width:21%"><col style="width:25%"></colgroup>
        <tr><td class="title" colspan="6">HISTORIAL DE MOVIMIENTOS</td></tr>
        <tr><td class="subtitle" colspan="6">IES José Santos Chocano · ' . $escape(date('d/m/Y', strtotime($filters['start'])) . ' — ' . date('d/m/Y', strtotime($filters['end']))) . ' · Generado: ' . $escape(date('d/m/Y H:i')) . '</td></tr>
        <tr><td class="summary" colspan="6">Registros exportados: ' . count($logs) . '</td></tr>
        <tr><td colspan="6"></td></tr>
        <tr class="headers"><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Módulo</th><th>Descripción</th><th>Datos registrados</th></tr>';
        foreach ($logs as $index => $log) {
            $class = $index % 2 === 1 ? 'data alt' : 'data';
            echo '<tr class="' . $class . '">'
                . '<td class="text">' . $escape(date('d/m/Y H:i:s', strtotime($log['created_at']))) . '</td>'
                . '<td>' . $escape($log['actor_name']) . '</td>'
                . '<td>' . $escape($this->labelFor($log['action'])) . '</td>'
                . '<td>' . $escape($this->labelFor($log['entity']) . ($log['entity_id'] !== null ? ' #' . (int) $log['entity_id'] : '')) . '</td>'
                . '<td>' . $escape($log['description']) . '</td>'
                . '<td>' . $escape($this->formatDetails($log['details_json'] ?? null)) . '</td></tr>';
        }
        echo '</table></body></html>';
        exit;
    }

    private function filters($source) {
        return [
            'start' => $this->inputValue($source, 'start', date('Y-m-01')),
            'end' => $this->inputValue($source, 'end', date('Y-m-d')),
            'action' => $this->inputValue($source, 'action', ''),
            'entity' => $this->inputValue($source, 'entity', ''),
            'search' => $this->inputValue($source, 'search', ''),
        ];
    }

    private function inputValue($source, $key, $default) {
        $value = $source[$key] ?? $default;
        return is_scalar($value) ? trim((string) $value) : $default;
    }

    private function validDateRange($start, $end) {
        foreach ([$start, $end] as $date) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
            [$year, $month, $day] = array_map('intval', explode('-', $date));
            if (!checkdate($month, $day, $year)) return false;
        }
        return $start <= $end;
    }

    private function whereClause($filters, &$params) {
        $where = ['creado_en >= :start_at', 'creado_en < DATE_ADD(:end_date, INTERVAL 1 DAY)'];
        $params = ['start_at' => $filters['start'] . ' 00:00:00', 'end_date' => $filters['end']];
        if ($filters['action'] !== '') {
            $where[] = 'accion = :action';
            $params['action'] = $filters['action'];
        }
        if ($filters['entity'] !== '') {
            $where[] = 'entidad = :entity';
            $params['entity'] = $filters['entity'];
        }
        if ($filters['search'] !== '') {
            $where[] = '(nombre_actor LIKE :search OR descripcion LIKE :search OR id_entidad LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        return implode(' AND ', $where);
    }

    private function countLogs($filters) {
        $where = $this->whereClause($filters, $params);
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM bitacora_auditoria WHERE ' . $where);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function getLogs($filters, $limit = null, $offset = 0) {
        $where = $this->whereClause($filters, $params);
        $query = 'SELECT id, creado_en AS created_at, nombre_actor AS actor_name,
                         tipo_actor AS actor_type, accion AS action, entidad AS entity,
                         id_entidad AS entity_id, descripcion AS description,
                         detalles_json AS details_json, direccion_ip AS ip_address
                  FROM bitacora_auditoria WHERE ' . $where . ' ORDER BY creado_en DESC, id DESC';
        if ($limit !== null) $query .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($logs as &$log) {
            $log['action_label'] = $this->labelFor($log['action']);
            $log['entity_label'] = $this->labelFor($log['entity']);
            $log['details_text'] = $this->formatDetails($log['details_json'] ?? null);
        }
        unset($log);
        return $logs;
    }

    private function labelFor($value) {
        $labels = [
            'crear' => 'Registro', 'editar' => 'Actualización', 'eliminar' => 'Eliminación',
            'cambiar_estado' => 'Cambio de estado', 'cambiar_clave' => 'Cambio de clave',
            'entrada' => 'Entrada', 'salida' => 'Salida', 'salida_temporal' => 'Salida temporal',
            'salida_final' => 'Salida final', 'reingreso' => 'Reingreso',
            'iniciar_sesion' => 'Inicio de sesión', 'cerrar_sesion' => 'Cierre de sesión',
            'estudiante' => 'Estudiante', 'asistencia' => 'Asistencia', 'visitante' => 'Visitante',
            'especialidad' => 'Especialidad', 'usuario' => 'Usuario', 'configuracion' => 'Configuración',
            'incidencia' => 'Incidencia', 'sesion' => 'Sesión'
        ];
        return $labels[$value] ?? ucfirst(str_replace('_', ' ', (string) $value));
    }

    private function formatDetails($json) {
        if (!$json) return '—';
        $details = json_decode($json, true);
        if (!is_array($details)) return 'Sin detalle disponible';
        $fields = [
            'employee_code' => 'Código', 'first_name' => 'Nombres', 'last_name' => 'Apellidos',
            'dni' => 'DNI', 'age' => 'Edad', 'sex' => 'Sexo', 'email' => 'Correo',
            'department_id' => 'Especialidad', 'cycle_id' => 'Ciclo', 'semester_id' => 'Semestre',
            'shift' => 'Turno', 'status' => 'Estado', 'hora_entrada' => 'Hora de entrada',
            'hora_salida' => 'Hora de salida', 'hora_reingreso' => 'Hora de reingreso',
            'motivo' => 'Motivo', 'fecha' => 'Fecha', 'estado' => 'Estado', 'estudiante_id' => 'Estudiante'
        ];
        $sections = [];
        foreach (['before' => 'Antes', 'after' => 'Después'] as $key => $title) {
            if (empty($details[$key]) || !is_array($details[$key])) continue;
            $values = [];
            foreach ($details[$key] as $field => $value) {
                if ($field === 'password_changed') { $values[] = 'Contraseña: ' . ($value ? 'actualizada' : 'sin cambios'); continue; }
                if ($value === null || $value === '') continue;
                $label = $fields[$field] ?? ucfirst(str_replace('_', ' ', $field));
                $values[] = $label . ': ' . (is_scalar($value) ? (string) $value : 'actualizado');
            }
            if ($values) $sections[] = $title . ': ' . implode(' · ', $values);
        }
        if (!$sections && isset($details['motivo']) && $details['motivo'] !== '') $sections[] = 'Motivo: ' . $details['motivo'];
        return $sections ? implode(' | ', $sections) : '—';
    }
}
