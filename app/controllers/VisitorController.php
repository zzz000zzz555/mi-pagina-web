<?php
require_once '../app/config/db.php';
require_once '../app/models/Visitor.php';

class VisitorController {

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'guardia'], true)) {
            header('Location: ?c=Auth&a=login');
            exit;
        }
    }
    
    public function register() {
        $database = new Database();
        $db = $database->getConnection();
        $visitorModel = new Visitor($db);

        $message = "";
        $error = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $action = $_POST['action_type'] ?? '';
            $dni = trim($_POST['dni'] ?? '');

            if ($action == 'entrada') {
                $name = trim($_POST['full_name'] ?? '');
                $company = trim($_POST['company'] ?? '');
                $reason = trim($_POST['reason'] ?? '');

                if ($visitorModel->registerEntry($dni, $name, $company, $reason)) {
                    $message = "✅ Visita registrada. Puede pasar.";
                } else {
                    $error = "❌ Error al registrar la visita.";
                }

            } elseif ($action == 'salida') {
                $result = $visitorModel->registerExit($dni);
                if ($result) {
                    $message = "👋 " . $result;
                } else {
                    $error = "❌ No se encontró una entrada abierta para el DNI: $dni";
                }
            }
        }

        // Redirigimos al Kiosco con el mensaje
        // Nota: Usamos parámetros URL para pasar el mensaje al Kiosco
        // En un sistema real usaríamos Session Flash, pero esto es más simple.
        header("Location: ?c=Attendance&msg=" . urlencode($message) . "&err=" . urlencode($error));
    }
}
?>
