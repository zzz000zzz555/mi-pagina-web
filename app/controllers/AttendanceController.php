<?php
require_once '../app/models/Employee.php';
require_once '../app/models/Setting.php';
require_once '../app/libs/AuditLog.php';

class AttendanceController {

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        // El QR es un control institucional: solo vigilante o administrador lo operan.
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'guardia'], true)) {
            header('Location: ?c=Auth&a=login');
            exit;
        }
    }
    
    // 1. Muestra la vista del Kiosco (Pantalla con cámara)
    public function index() {
        $database = new Database();
        $setting = new Setting($database->getConnection());
        $schedules = [
            'manana' => ['entrada' => $setting->get('morning_entry_time') ?: '08:00', 'salida' => $setting->get('morning_exit_time') ?: '13:00'],
            'tarde' => ['entrada' => $setting->get('afternoon_entry_time') ?: '14:00', 'salida' => $setting->get('afternoon_exit_time') ?: '19:00']
        ];
        require_once '../app/views/attendance/kiosk.php';
    }

    /**
     * Últimos movimientos para el panel en vivo del kiosco.
     * La consulta queda limitada a diez filas para que la pantalla siga siendo
     * ágil incluso cuando la institución tenga muchos registros históricos.
     */
    public function recent() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        try {
            $database = new Database();
            $db = $database->getConnection();
            $sql = "SELECT m.id, m.tipo, m.fecha_hora, m.motivo,
                           e.codigo_estudiante,
                           CONCAT(e.nombres, ' ', e.apellidos) AS estudiante
                    FROM movimientos_asistencia m
                    INNER JOIN estudiantes e ON e.id = m.estudiante_id
                    WHERE m.fecha_hora >= CURDATE()
                      AND m.fecha_hora < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                    ORDER BY m.fecha_hora DESC, m.id DESC
                    LIMIT 10";
            $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'data' => []]);
        }
        exit;
    }

    // 2. Procesa el escaneo del código QR
    public function register() {
        $database = new Database();
        $db = $database->getConnection();
        $isAjax = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['ajax'] ?? '') === '1';
        
        // Inicializamos mensaje vacio
        $message = "";
        $error = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Recibimos el código del escáner
            $code = strtoupper(trim($_POST['employee_code'] ?? ''));
            $action = $_POST['action_type'] ?? 'entrada';
            $reason = trim($_POST['exit_reason'] ?? '');
            if (!in_array($action, ['entrada', 'salida_temporal', 'salida_final', 'reingreso'], true)) $action = 'entrada';

            if(!empty($code)) {
                // A. Buscar al empleado por su código
                $queryEmp = "SELECT id, nombres AS first_name, apellidos AS last_name, turno AS shift
                             FROM estudiantes
                             WHERE codigo_estudiante = :code AND estado = 'activo' LIMIT 1";
                $stmtEmp = $db->prepare($queryEmp);
                $stmtEmp->bindParam(':code', $code);
                $stmtEmp->execute();
                $empleado = $stmtEmp->fetch(PDO::FETCH_ASSOC);

                if ($empleado) {
                    $empId = $empleado['id'];
                    $studentName = trim($empleado['first_name'] . ' ' . $empleado['last_name']);
                    $fechaHoy = date('Y-m-d');
                    $horaActual = date('H:i:s');

                    // B. Un registro diario y sus movimientos detallados.
                    $queryCheck = "SELECT id, hora_entrada, hora_salida FROM registros_asistencia
                                   WHERE estudiante_id = :empId AND fecha = :fechaHoy";
                    $stmtCheck = $db->prepare($queryCheck);
                    $stmtCheck->bindParam(':empId', $empId);
                    $stmtCheck->bindParam(':fechaHoy', $fechaHoy);
                    $stmtCheck->execute();
                    $registroHoy = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                    if (!$registroHoy) {
                        if (!in_array($action, ['entrada', 'reingreso'], true)) {
                            $error = $studentName . ' aún no registra entrada hoy. Seleccione Entrada / retorno para comenzar.';
                        } else {
                        $setting = new Setting($db);
                        $settingName = $empleado['shift'] === 'tarde' ? 'afternoon_entry_time' : 'morning_entry_time';
                        $limit = $setting->get($settingName) ?: ($empleado['shift'] === 'tarde' ? '14:00' : '08:00');
                        $limit = substr($limit, 0, 5) . ':00';
                        $status = $horaActual > $limit ? 'tarde' : 'a_tiempo';
                        $insert = "INSERT INTO registros_asistencia (estudiante_id, fecha, hora_entrada, estado)
                                   VALUES (:empId, :fechaHoy, :horaActual, :status)";
                        $stmtInsert = $db->prepare($insert);
                        $stmtInsert->bindParam(':empId', $empId);
                        $stmtInsert->bindParam(':fechaHoy', $fechaHoy);
                        $stmtInsert->bindParam(':horaActual', $horaActual);
                        $stmtInsert->bindParam(':status', $status);
                        
                        try {
                            $db->beginTransaction();
                            $stmtInsert->execute();
                            $logId = (int) $db->lastInsertId();
                            $this->saveMovement($db, $logId, $empId, 'entrada', $fechaHoy . ' ' . $horaActual);
                            $db->commit();
                            AuditLog::record($db, 'entrada', 'asistencia', $logId, 'Se registró la entrada de ' . $studentName . '.', ['after' => ['estudiante_id' => (int) $empId, 'fecha' => $fechaHoy, 'hora_entrada' => $horaActual, 'estado' => $status]]);
                            $message = "Entrada registrada para " . $studentName . ". Estado: " . ($status === 'tarde' ? 'tardanza' : 'a tiempo') . ".";
                        } catch (Throwable $exception) {
                            if ($db->inTransaction()) $db->rollBack();
                            $error = 'No se pudo registrar la entrada.';
                        }
                        }

                    } else {
                        $last = $this->lastMovement($db, (int) $registroHoy['id']);
                        $lastType = $last['tipo'] ?? ($registroHoy['hora_salida'] === null ? 'entrada' : 'salida_final');
                        if ($lastType === 'salida_final') {
                            $error = $studentName . ' ya registró su salida final hoy. Escanee el QR de otro estudiante.';
                        } elseif ($lastType === 'salida_temporal') {
                            if (!in_array($action, ['entrada', 'reingreso'], true)) $error = $studentName . ' tiene una salida temporal. Seleccione Entrada / retorno para registrar su regreso.';
                            else {
                                $this->saveMovement($db, (int) $registroHoy['id'], $empId, 'reingreso', $fechaHoy . ' ' . $horaActual);
                                $db->prepare('UPDATE registros_asistencia SET hora_salida = NULL, horas_totales = NULL WHERE id = :id')->execute(['id' => $registroHoy['id']]);
                                AuditLog::record($db, 'reingreso', 'asistencia', (int) $registroHoy['id'], 'Se registró el reingreso de ' . $studentName . '.', ['after' => ['hora_reingreso' => $horaActual]]);
                                $message = 'Retorno registrado para ' . $studentName . '.';
                            }
                        } elseif (!in_array($action, ['salida_temporal', 'salida_final'], true)) {
                            $error = $studentName . ' está dentro de la institución. Seleccione Salida temporal o Salida final antes de escanear.';
                        } elseif ($action === 'salida_temporal' && $reason === '') {
                            $error = 'Indique el motivo de salida temporal para ' . $studentName . ' (por ejemplo: médico o refrigerio).';
                        } else {
                            $type = $action;
                            $this->saveMovement($db, (int) $registroHoy['id'], $empId, $type, $fechaHoy . ' ' . $horaActual, $reason ?: null);
                            $hours = $this->calculateAttendanceHours($db, (int) $registroHoy['id']);
                            $db->prepare('UPDATE registros_asistencia SET hora_salida = :hora, horas_totales = :horas WHERE id = :id')->execute(['hora' => $horaActual, 'horas' => $hours, 'id' => $registroHoy['id']]);
                            AuditLog::record($db, $type, 'asistencia', (int) $registroHoy['id'], 'Se registró la salida de ' . $studentName . '.', ['after' => ['hora_salida' => $horaActual, 'motivo' => $reason]]);
                            $message = ($type === 'salida_final' ? 'Salida final' : 'Salida temporal') . ' registrada para ' . $studentName . '.';
                        }
                    }
                } else {
                    $error = "Código QR no válido o estudiante no encontrado.";
                }
            }
        }

        // El kiosco usa AJAX para continuar escaneando varios estudiantes sin recargar la pantalla.
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => $error === '',
                'message' => $error !== '' ? $error : $message
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Volver a cargar la vista con los mensajes cuando se use el formulario tradicional.
        $setting = new Setting($db);
        $schedules = ['manana' => ['entrada' => $setting->get('morning_entry_time') ?: '08:00', 'salida' => $setting->get('morning_exit_time') ?: '13:00'], 'tarde' => ['entrada' => $setting->get('afternoon_entry_time') ?: '14:00', 'salida' => $setting->get('afternoon_exit_time') ?: '19:00']];
        require_once '../app/views/attendance/kiosk.php';
    }

    private function saveMovement($db, $logId, $studentId, $type, $dateTime, $reason = null) {
        $stmt = $db->prepare('INSERT INTO movimientos_asistencia (registro_asistencia_id, estudiante_id, tipo, fecha_hora, motivo) VALUES (:registro, :estudiante, :tipo, :fecha_hora, :motivo)');
        $stmt->execute(['registro' => $logId, 'estudiante' => $studentId, 'tipo' => $type, 'fecha_hora' => $dateTime, 'motivo' => $reason]);
    }

    private function lastMovement($db, $logId) {
        $stmt = $db->prepare('SELECT tipo FROM movimientos_asistencia WHERE registro_asistencia_id = :id ORDER BY fecha_hora DESC, id DESC LIMIT 1');
        $stmt->execute(['id' => $logId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function calculateAttendanceHours($db, $logId) {
        $stmt = $db->prepare('SELECT tipo, fecha_hora FROM movimientos_asistencia WHERE registro_asistencia_id = :id ORDER BY fecha_hora, id');
        $stmt->execute(['id' => $logId]);
        $startedAt = null; $seconds = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $movement) {
            if (in_array($movement['tipo'], ['entrada', 'reingreso'], true)) $startedAt = strtotime($movement['fecha_hora']);
            elseif ($startedAt !== null && in_array($movement['tipo'], ['salida_temporal', 'salida_final'], true)) { $seconds += max(0, strtotime($movement['fecha_hora']) - $startedAt); $startedAt = null; }
        }
        return round($seconds / 3600, 2);
    }
}
?>
