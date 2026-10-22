<?php
require_once '../app/config/db.php';
require_once '../app/models/Setting.php';

class SettingController {
    private $settingModel;

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        // Solo Admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
            header("Location: ?c=Dashboard");
            exit;
        }

        $database = new Database();
        $this->settingModel = new Setting($database->getConnection());
    }

    public function index() {
        $morning_entry_time = $this->settingModel->get('morning_entry_time') ?: '08:00';
        $morning_exit_time = $this->settingModel->get('morning_exit_time') ?: '13:00';
        $afternoon_entry_time = $this->settingModel->get('afternoon_entry_time') ?: '14:00';
        $afternoon_exit_time = $this->settingModel->get('afternoon_exit_time') ?: '19:00';
        require_once '../app/views/settings/index.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $morning = $_POST['morning_entry_time'] ?? '';
            $morningExit = $_POST['morning_exit_time'] ?? '';
            $afternoon = $_POST['afternoon_entry_time'] ?? '';
            $afternoonExit = $_POST['afternoon_exit_time'] ?? '';
            if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $morning)
                || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $morningExit)
                || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $afternoon)
                || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $afternoonExit)
                || $morning >= $morningExit || $afternoon >= $afternoonExit) {
                header("Location: ?c=Setting&err=horario_invalido");
                exit;
            }
            $this->settingModel->set('morning_entry_time', $morning);
            $this->settingModel->set('morning_exit_time', $morningExit);
            $this->settingModel->set('afternoon_entry_time', $afternoon);
            $this->settingModel->set('afternoon_exit_time', $afternoonExit);
            header("Location: ?c=Setting&msg=guardado");
        }
    }
}
?>
