<?php
require_once __DIR__ . '/../libs/AuditLog.php';

class Incident {
    private $conn;
    private $table = "incidencias";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. REGISTRAR INCIDENCIA (Ahora con adjunto opcional)
    public function create($title, $description, $severity, $userId, $attachment = null) {
        $query = "INSERT INTO " . $this->table . " (titulo, descripcion, gravedad, creado_por, adjunto) 
                  VALUES (:title, :desc, :sev, :uid, :att)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':desc', $description);
        $stmt->bindParam(':sev', $severity);
        $stmt->bindParam(':uid', $userId);
        $stmt->bindParam(':att', $attachment);
        $saved = $stmt->execute();
        if ($saved) {
            AuditLog::record($this->conn, 'crear', 'incidencia', (int) $this->conn->lastInsertId(), 'Se registró la incidencia: ' . $title . '.', [
                'after' => ['title' => $title, 'severity' => $severity, 'created_by' => (int) $userId, 'attachment' => $attachment]
            ]);
        }
        return $saved;
    }

    // 2. LISTAR CON FILTROS
    public function getWithFilters($start, $end, $search = "") {
        $query = "SELECT i.id, i.titulo AS title, i.descripcion AS description,
                         i.gravedad AS severity, i.creado_por AS created_by,
                         i.creado_en AS created_at, i.adjunto AS attachment,
                         u.nombre_usuario AS reporter
                  FROM " . $this->table . " i
                  LEFT JOIN usuarios u ON i.creado_por = u.id
                  WHERE (i.creado_en BETWEEN :start AND :end)";

        if (!empty($search)) {
            $query .= " AND (i.titulo LIKE :search OR i.descripcion LIKE :search OR u.nombre_usuario LIKE :search)";
        }

        $query .= " ORDER BY i.creado_en DESC";

        $stmt = $this->conn->prepare($query);
        
        $startFull = $start . " 00:00:00";
        $endFull = $end . " 23:59:59";

        $stmt->bindParam(':start', $startFull);
        $stmt->bindParam(':end', $endFull);

        if (!empty($search)) {
            $term = "%" . $search . "%";
            $stmt->bindParam(':search', $term);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>