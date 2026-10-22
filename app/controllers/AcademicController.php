<?php
require_once '../app/config/db.php';
require_once '../app/models/Setting.php';

class AcademicController {
    private $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: ?c=Dashboard'); exit;
        }
        $this->db = (new Database())->getConnection();
    }

    public function cycles() { $this->catalog('ciclos', 'Ciclos', 'Ciclo'); }
    public function semesters() { $this->catalog('semestres', 'Semestres', 'Semestre'); }

    private function catalog($table, $title, $singular) {
        $items = $this->db->query("SELECT id, nombre FROM `$table` ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $section = $table;
        require '../app/views/academic/catalog.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?c=Academic&a=cycles'); exit; }
        $table = $this->allowedTable($_POST['section'] ?? '');
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
        $name = trim($_POST['name'] ?? '');
        if (!$id || $name === '') { $this->back($table, 'err=datos'); }
        try {
            $stmt = $this->db->prepare("INSERT INTO `$table` (id, nombre) VALUES (:id, :name)");
            $stmt->execute(['id' => $id, 'name' => $name]);
            $this->back($table, 'msg=creado');
        } catch (PDOException $e) { $this->back($table, 'err=duplicado'); }
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?c=Academic&a=cycles'); exit; }
        $table = $this->allowedTable($_POST['section'] ?? '');
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        if (!$id || $name === '') $this->back($table, 'err=datos');
        $stmt = $this->db->prepare("UPDATE `$table` SET nombre = :name WHERE id = :id");
        $stmt->execute(['id' => $id, 'name' => $name]);
        $this->back($table, 'msg=actualizado');
    }

    public function shifts() {
        $settings = new Setting($this->db);
        $morningEntry = $settings->get('morning_entry_time') ?: '08:00'; $morningExit = $settings->get('morning_exit_time') ?: '13:00';
        $afternoonEntry = $settings->get('afternoon_entry_time') ?: '14:00'; $afternoonExit = $settings->get('afternoon_exit_time') ?: '19:00';
        require '../app/views/academic/shifts.php';
    }

    public function updateShifts() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?c=Academic&a=shifts'); exit; }
        $values = ['morning_entry_time'=>$_POST['morning_entry_time']??'', 'morning_exit_time'=>$_POST['morning_exit_time']??'', 'afternoon_entry_time'=>$_POST['afternoon_entry_time']??'', 'afternoon_exit_time'=>$_POST['afternoon_exit_time']??''];
        foreach ($values as $value) if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) { header('Location: ?c=Academic&a=shifts&err=horario'); exit; }
        if ($values['morning_entry_time'] >= $values['morning_exit_time'] || $values['afternoon_entry_time'] >= $values['afternoon_exit_time']) { header('Location: ?c=Academic&a=shifts&err=horario'); exit; }
        $settings = new Setting($this->db); foreach ($values as $key=>$value) $settings->set($key, $value);
        header('Location: ?c=Academic&a=shifts&msg=guardado');
    }

    private function allowedTable($table) { return in_array($table, ['ciclos','semestres'], true) ? $table : 'ciclos'; }
    private function back($table, $query) { header('Location: ?c=Academic&a=' . ($table === 'ciclos' ? 'cycles' : 'semesters') . '&' . $query); exit; }
}
