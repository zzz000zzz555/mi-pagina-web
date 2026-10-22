<?php
require_once '../app/config/db.php';
require_once '../app/models/Attendance.php';
require_once '../app/models/Employee.php';
require_once '../app/models/Setting.php'; // <--- IMPORTANTE: Nuevo Modelo

class DashboardController {
    private $db;
    private $attendanceModel;
    private $employeeModel;
    private $settingModel; // Variable para la configuración

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        
        // 1. Verificar Login
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?c=Auth&a=login");
            exit;
        }

        // 2. Conexión y Modelos
        $database = new Database();
        $this->db = $database->getConnection();
        
        $this->attendanceModel = new Attendance($this->db);
        $this->employeeModel = new Employee($this->db);
        $this->settingModel = new Setting($this->db); // <--- Inicializamos
    }

    public function index() {
        // A. OBTENER LA HORA DE ENTRADA DE LA BD
        // Si no existe en BD, usamos '08:00:00' por defecto para que no falle
        $horaEntrada = $this->settingModel->get('morning_entry_time');
        if (!$horaEntrada) $horaEntrada = '08:00:00';

        // B. Estadísticas Generales
        $totalEmployees = $this->countEmployees(); 
        $attendanceToday = $this->attendanceModel->countToday();
        
        // C. Contar Tardanzas (Usando la hora dinámica)
        $totalLates = $this->attendanceModel->countLatesToday();
        
        // D. Obtener lista reciente
        $recentLogs = $this->attendanceModel->getRecentLogs();

        // E. Datos para el Gráfico
        $chartData = $this->attendanceModel->getWeeklyStats();
        $labels = [];
        $dataValues = [];
        
        foreach($chartData as $day) {
            $labels[] = date('d/m', strtotime($day['date_log']));
            $dataValues[] = $day['total'];
        }
        // Evitar error de JS si no hay datos
        if (empty($labels)) { 
            $labels[] = date('d/m'); 
            $dataValues[] = 0; 
        }

        // F. Cargar vista
        require_once '../app/views/dashboard/index.php';
    }

    // Método auxiliar privado
    private function countEmployees() {
        $query = "SELECT COUNT(*) as total FROM estudiantes WHERE estado = 'activo'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>
