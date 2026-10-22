<?php
require_once '../app/config/db.php';
require_once '../app/models/Attendance.php';
require_once '../app/models/Employee.php';
require_once '../app/models/Setting.php'; // 1. IMPORTAMOS EL MODELO DE AJUSTES

class ReportController {
    private $attendanceModel;
    private $employeeModel;
    private $settingModel; // 2. VARIABLE NUEVA
    private $db;

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?c=Auth&a=login");
            exit;
        }
        
        if ($_SESSION['role'] != 'admin') {
            header("Location: ?c=Dashboard");
            exit;
        }

        $database = new Database();
        $this->db = $database->getConnection();
        $this->attendanceModel = new Attendance($this->db);
        $this->employeeModel = new Employee($this->db);
        $this->settingModel = new Setting($this->db); // 3. INICIALIZAR
    }

    public function index() { require_once '../app/views/reports/index.php'; }

    public function monthly() {
        extract($this->monthlyData($_GET));
        require '../app/views/reports/monthly.php';
    }

    public function monthlyExport() {
        extract($this->monthlyData($_GET));
        $escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="Registro_asistencia_' . $month . '.xls"');
        echo "\xEF\xBB\xBF";
        echo '<html><head><meta charset="UTF-8"><style>table{border-collapse:collapse;font-family:Calibri}th{background:#265b41;color:#fff}th,td{border:1px solid #607080;padding:5px;text-align:center}.student{text-align:left;white-space:nowrap;width:280px}.a{background:#d9f2e5;color:#087d53;font-weight:bold}.t{background:#fff1d6;color:#a65b00;font-weight:bold}.f{background:#fde2e5;color:#b42335;font-weight:bold}.title{font-size:18pt;font-weight:bold;background:#eef5f0;text-align:left}.month{background:#e8f1f7;color:#19455e;font-weight:bold}.day{background:#f4f7f9;font-weight:bold}</style></head><body><table><tr><td class="title" colspan="' . (count($days)+4) . '">REGISTRO DE ASISTENCIA</td></tr><tr><td colspan="' . (count($days)+4) . '">Período: ' . $escape(date('d/m/Y', strtotime($start))) . ' al ' . $escape(date('d/m/Y', strtotime($end))) . ' · A = Asistió · T = Tardanza · F = Faltó</td></tr><tr><th rowspan="3">N.L.</th><th rowspan="3">CÓDIGO</th><th rowspan="3" class="student">NOMBRE DEL ESTUDIANTE</th>';
        foreach ($weeks as $week) echo '<th colspan="' . count($week['days']) . '">' . $escape($week['label']) . '</th>';
        echo '<th rowspan="3">FALTAS</th></tr><tr>';
        foreach ($months as $group) echo '<th colspan="' . count($group['days']) . '" class="month">' . $escape($group['label']) . '</th>';
        echo '</tr><tr>';
        foreach ($days as $day) echo '<th class="day">' . date('d', strtotime($day)) . '</th>';
        echo '</tr>';
        foreach ($students as $index => $student) { $absences=0; echo '<tr><td>' . ($index+1) . '</td><td>' . $escape($student['codigo_estudiante']) . '</td><td class="student">' . $escape($student['nombre_planilla']) . '</td>'; foreach ($days as $day) { $mark=$attendance[(int)$student['id']][$day] ?? 'F'; if($mark === 'F') $absences++; echo '<td class="' . strtolower($mark) . '">' . $mark . '</td>'; } echo '<td class="f">' . $absences . '</td></tr>'; }
        echo '</table></body></html>'; exit;
    }

    private function monthlyData($source) {
        $month = $source['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) $month = date('Y-m');
        $filters = $this->reportFilters($source);
        $defaultStart = $month . '-01'; $defaultEnd = date('Y-m-t', strtotime($defaultStart));
        $start = $source['start_date'] ?? $defaultStart;
        $end = $source['end_date'] ?? $defaultEnd;
        // La planilla docente puede abarcar varios meses (por ejemplo, enero a octubre).
        // Se admite hasta un año completo; la tabla conserva desplazamiento horizontal.
        if (!$this->validDateRange($start, $end) || (strtotime($end) - strtotime($start)) > 366 * 86400) {
            $start = $defaultStart; $end = $defaultEnd;
        }
        $days = [];
        for ($date = new DateTime($start); $date <= new DateTime($end); $date->modify('+1 day')) {
            if ((int) $date->format('N') <= 6) $days[] = $date->format('Y-m-d');
        }
        $where = ["e.estado = 'activo'"]; $params = [];
        foreach (['department_id'=>'e.especialidad_id','cycle_id'=>'e.ciclo_id','semester_id'=>'e.semestre_id','shift'=>'e.turno'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') { $where[] = "$column = :$key"; $params[$key] = $key === 'shift' ? $filters[$key] : (int) $filters[$key]; }
        }
        $studentSql = "SELECT e.id, e.nombres, e.apellidos, e.dni, e.codigo_estudiante, d.nombre AS especialidad, c.nombre AS ciclo, s.nombre AS semestre, e.turno FROM estudiantes e LEFT JOIN especialidades d ON d.id=e.especialidad_id LEFT JOIN ciclos c ON c.id=e.ciclo_id LEFT JOIN semestres s ON s.id=e.semestre_id WHERE " . implode(' AND ', $where) . " ORDER BY e.apellidos,e.nombres";
        $stmt = $this->db->prepare($studentSql); $stmt->execute($params); $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($students as &$student) {
            $fullName = trim(($student['apellidos'] ?? '') . ' ' . ($student['nombres'] ?? ''));
            $student['nombre_planilla'] = function_exists('mb_strtoupper')
                ? mb_strtoupper($fullName, 'UTF-8')
                : strtoupper($fullName);
        }
        unset($student);
        $attendance = [];
        if ($students) {
            $ids = array_column($students, 'id'); $marks = implode(',', array_fill(0, count($ids), '?'));
            $log = $this->db->prepare("SELECT estudiante_id, fecha, estado FROM registros_asistencia WHERE fecha BETWEEN ? AND ? AND estudiante_id IN ($marks)");
            $log->execute(array_merge([$start, $end], $ids));
            foreach ($log->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $attendance[(int)$row['estudiante_id']][$row['fecha']] = ($row['estado'] ?? '') === 'tarde' ? 'T' : 'A';
            }
        }
        $departments = $this->db->query("SELECT id,nombre AS name FROM especialidades WHERE estado='activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $cycles = $this->db->query("SELECT id,nombre AS name FROM ciclos ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $semesters = $this->db->query("SELECT id,nombre AS name FROM semestres ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $weeks = []; $months = []; $monthNames = [1=>'ENERO',2=>'FEBRERO',3=>'MARZO',4=>'ABRIL',5=>'MAYO',6=>'JUNIO',7=>'JULIO',8=>'AGOSTO',9=>'SETIEMBRE',10=>'OCTUBRE',11=>'NOVIEMBRE',12=>'DICIEMBRE'];
        foreach ($days as $day) { $key = date('o-W', strtotime($day)); if (!isset($weeks[$key])) $weeks[$key] = ['label' => 'Semana ' . (count($weeks) + 1), 'days' => []]; $weeks[$key]['days'][] = $day; }
        foreach ($days as $day) { $key = date('Y-m', strtotime($day)); if (!isset($months[$key])) $months[$key] = ['label' => $monthNames[(int) date('n', strtotime($day))], 'days' => []]; $months[$key]['days'][] = $day; }
        return compact('month', 'filters', 'start', 'end', 'days', 'weeks', 'months', 'students', 'attendance', 'departments', 'cycles', 'semesters');
    }

    public function history() {
        $start_date = $_GET['start'] ?? date('Y-m-01');
        $end_date = $_GET['end'] ?? date('Y-m-d');
        $specificDate = $_GET['specific_date'] ?? '';
        if ($this->validDateRange($specificDate, $specificDate)) {
            $start_date = $specificDate;
            $end_date = $specificDate;
        }
        $rangeError = !$this->validDateRange($start_date, $end_date);
        if ($rangeError) {
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-d');
        }
        $filters = $this->reportFilters($_GET);
        // La pantalla muestra una cantidad controlada para mantener los filtros ágiles.
        // La exportación usa getHistoryByDate() y conserva todos los resultados filtrados.
        $historyLimit = 100;
        $logs = $this->attendanceModel->getLogsWithFilters($filters, $start_date, $end_date, $historyLimit);

        $departments = $this->db->query("SELECT id, nombre AS name FROM especialidades WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $cycles = $this->db->query("SELECT id, nombre AS name FROM ciclos ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $semesters = $this->db->query("SELECT id, nombre AS name FROM semestres ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $students = $this->db->query("SELECT id, CONCAT(apellidos, ', ', nombres, ' · DNI ', COALESCE(dni, 'Sin DNI')) AS label FROM estudiantes WHERE estado = 'activo' ORDER BY apellidos, nombres")->fetchAll(PDO::FETCH_ASSOC);
        $generatedAt = date('d/m/Y H:i:s');
        $reportTitle = $this->reportTitle($filters);
        
        require_once '../app/views/reports/history.php';
    }

    public function export() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?c=Report');
            exit;
        }

        $start = $_POST['start_date'] ?? '';
        $end = $_POST['end_date'] ?? '';
        if (!$this->validDateRange($start, $end)) {
            header('Location: ?c=Report&err=rango_fechas');
            exit;
        }
        $filters = $this->reportFilters($_POST);
        $data = $this->attendanceModel->getHistoryByDate($start, $end, $filters);
        $selectedIds = $_POST['attendance_ids'] ?? [];
        if (is_array($selectedIds) && count($selectedIds) > 0) {
            $selectedIds = array_flip(array_filter(array_map('intval', $selectedIds), static fn($id) => $id > 0));
            $data = array_values(array_filter($data, static fn($row) => isset($selectedIds[(int) $row['attendance_id']])));
        }
        $generatedAt = date('d/m/Y H:i:s');
        $reportTitle = $this->reportTitle($filters);
        $total = count($data);
        $late = count(array_filter($data, static fn($row) => ($row['status'] ?? '') === 'tarde'));
        $completed = count(array_filter($data, static fn($row) => !empty($row['check_out_time'])));
        $onTime = $total - $late;
        $escape = static function ($value) {
            $value = (string) $value;
            if (preg_match('/^[=+\-@]/', $value)) $value = "'" . $value;
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="Asistencia_' . date('Ymd_His') . '.xls"');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        echo "\xEF\xBB\xBF";
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
            body{font-family:Calibri,Arial,sans-serif;color:#172b3a}
            .report{width:100%;border-collapse:collapse;table-layout:fixed}
            .brand{background:#0d3249;color:#fff;font-size:22pt;font-weight:bold;padding:19px 18px;letter-spacing:.2px}
            .subtitle{background:#eaf3f6;color:#33536a;font-size:11pt;padding:9px 18px;border-bottom:1px solid #c7dce5}
            .meta-label{background:#f2f6f8;color:#486476;font-weight:bold;padding:8px 10px;border:1px solid #d8e5eb}
            .meta-value{padding:8px 10px;border:1px solid #d8e5eb;color:#172b3a}
            .metric-label{background:#176b87;color:#fff;font-size:9pt;font-weight:bold;padding:8px 9px;text-align:center;border:1px solid #12566d}
            .metric-value{background:#eef7f9;color:#123d50;font-size:15pt;font-weight:bold;padding:8px 9px;text-align:center;border:1px solid #cbe1e8}
            .section{background:#dcecf1;color:#0d3249;font-weight:bold;padding:8px 10px;border-top:2px solid #176b87}
            .headers th{background:#155570;color:#fff;font-size:9pt;font-weight:bold;padding:10px 7px;border:1px solid #10465e;text-align:left;vertical-align:middle}
            .data td{padding:8px 7px;border:1px solid #d7e3e8;vertical-align:top;font-size:9pt;word-wrap:break-word}
            .alt td{background:#f4f8fa}.center{text-align:center}.text{mso-number-format:"\\@"}
            .good{color:#087d53;font-weight:bold}.late{color:#c52c3e;font-weight:bold}.pending{color:#875d00;font-weight:bold}
            .w-num{width:4%}.w-student{width:16%}.w-dni{width:9%}.w-training{width:14%}.w-shift{width:7%}.w-date{width:8%}.w-time{width:7%}.w-hours{width:8%}.w-status{width:8%}.w-move{width:16%}.w-reason{width:14%}
        </style></head><body><table class="report">
        <tr><td class="brand" colspan="12">IES JOSÉ SANTOS CHOCANO</td></tr>
        <tr><td class="subtitle" colspan="12">CONTROL DE ACCESO · ' . $escape($reportTitle) . '</td></tr>
        <tr><td class="meta-label" colspan="2">Periodo consultado</td><td class="meta-value" colspan="4">' . $escape(date('d/m/Y', strtotime($start)) . ' al ' . date('d/m/Y', strtotime($end))) . '</td><td class="meta-label" colspan="2">Generado el</td><td class="meta-value" colspan="4">' . $escape($generatedAt) . '</td></tr>
        <tr><td class="metric-label" colspan="3">REGISTROS</td><td class="metric-label" colspan="3">PUNTUALES</td><td class="metric-label" colspan="2">TARDANZAS</td><td class="metric-label" colspan="4">JORNADAS COMPLETAS</td></tr>
        <tr><td class="metric-value" colspan="3">' . $total . '</td><td class="metric-value" colspan="3">' . $onTime . '</td><td class="metric-value" colspan="2">' . $late . '</td><td class="metric-value" colspan="4">' . $completed . '</td></tr>
        <tr><td colspan="12">&nbsp;</td></tr>
        <tr><td class="section" colspan="12">DETALLE DE ASISTENCIA</td></tr>
        <tr class="headers"><th class="w-num center">N°</th><th class="w-student">Estudiante</th><th class="w-dni">DNI / Código</th><th class="w-training">Especialidad · ciclo · semestre</th><th class="w-shift center">Turno</th><th class="w-date center">Fecha</th><th class="w-time center">Entrada</th><th class="w-time center">Salida</th><th class="w-hours center">Tiempo efectivo</th><th class="w-status center">Puntualidad</th><th class="w-move">Movimientos</th><th class="w-reason">Motivo de salida</th></tr>';

        foreach ($data as $index => $row) {
            $hours = !empty($row['total_hours']) ? number_format((float) $row['total_hours'], 2) . ' h' : '—';
            $status = !empty($row['check_out_time']) ? 'Completado' : 'En turno';
            $punctuality = $row['status'] === 'tarde' ? 'Tarde' : 'Puntual';
            $class = $index % 2 === 1 ? 'data alt' : 'data';
            echo '<tr class="' . $class . '">'
                . '<td class="center">' . ($index + 1) . '</td>'
                . '<td><b>' . $escape($row['last_name'] . ', ' . $row['first_name']) . '</b></td>'
                . '<td class="text">' . $escape($row['dni']) . '<br><span style="color:#607788">' . $escape($row['employee_code']) . '</span></td>'
                . '<td><b>' . $escape($row['department'] ?? 'Sin especialidad') . '</b><br><span style="color:#607788">' . $escape(($row['cycle_name'] ?? 'Sin ciclo') . ' · ' . ($row['semester_name'] ?? 'Sin semestre')) . '</span></td>'
                . '<td class="center">' . $escape(ucfirst($row['shift'] ?? '—')) . '</td>'
                . '<td class="center">' . $escape(date('d/m/Y', strtotime($row['date_log']))) . '</td>'
                . '<td class="center ' . ($row['status'] === 'tarde' ? 'late' : 'good') . '">' . $escape($row['check_in_time']) . '</td>'
                . '<td class="center ' . ($status === 'Completado' ? 'good' : 'pending') . '">' . $escape($row['check_out_time'] ?? 'En turno') . '</td>'
                . '<td class="center"><b>' . $escape($hours) . '</b></td>'
                . '<td class="center ' . ($row['status'] === 'tarde' ? 'late' : 'good') . '">' . $escape($punctuality) . '</td>'
                . '<td>' . $escape($row['movimientos'] ?? '') . '</td>'
                . '<td>' . $escape($row['motivos_salida'] ?? '') . '</td>'
                . '</tr>';
        }
        echo '</table></body></html>';
        exit;
    }

    private function validDateRange($start, $end) {
        foreach ([$start, $end] as $date) {
            if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
            [$year, $month, $day] = array_map('intval', explode('-', $date));
            if (!checkdate($month, $day, $year)) return false;
        }
        return $start <= $end;
    }

    private function reportFilters($source) {
        return [
            'search' => $this->filterText($source, 'search'),
            'cycle_id' => $this->filterText($source, 'cycle_id'),
            'semester_id' => $this->filterText($source, 'semester_id'),
            'shift' => $this->filterText($source, 'shift'),
            'department_id' => $this->filterText($source, 'department_id'),
            'employee_id' => $this->filterText($source, 'employee_id')
        ];
    }

    private function filterText($source, $key) {
        $value = $source[$key] ?? '';
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function reportTitle($filters) {
        if ($filters['search'] !== '') {
            return 'REPORTE DE ASISTENCIA POR ESTUDIANTE O DNI: ' . $filters['search'];
        }
        if ($filters['cycle_id'] !== '') {
            $stmt = $this->db->prepare("SELECT nombre FROM ciclos WHERE id = :id");
            $stmt->execute(['id' => (int) $filters['cycle_id']]);
            $cycle = $stmt->fetchColumn();
            if ($cycle) return 'REPORTE DE ASISTENCIA POR CICLO: ' . $cycle;
        }
        return 'REPORTE GENERAL DE ASISTENCIA';
    }
}
?>
