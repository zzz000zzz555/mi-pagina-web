<?php
require_once __DIR__ . '/../libs/AuditLog.php';

class Visitor {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. REGISTRAR ENTRADA
    public function registerEntry($dni, $name, $company, $reason) {
        // Verificar si existe
        $queryCheck = "SELECT id FROM visitantes WHERE dni = :dni LIMIT 1";
        $stmtCheck = $this->conn->prepare($queryCheck);
        $stmtCheck->bindParam(':dni', $dni);
        $stmtCheck->execute();
        
        if ($row = $stmtCheck->fetch(PDO::FETCH_ASSOC)) {
            $visitorId = $row['id'];
        } else {
            // Crear nuevo
            $queryNew = "INSERT INTO visitantes (dni, nombre_completo, empresa) VALUES (:dni, :name, :company)";
            $stmtNew = $this->conn->prepare($queryNew);
            $stmtNew->bindParam(':dni', $dni);
            $stmtNew->bindParam(':name', $name);
            $stmtNew->bindParam(':company', $company);
            $stmtNew->execute();
            $visitorId = $this->conn->lastInsertId();
        }

        // Log de visita
        $queryLog = "INSERT INTO registros_visitantes (visitante_id, motivo, hora_entrada) VALUES (:vid, :reason, NOW())";
        $stmtLog = $this->conn->prepare($queryLog);
        $stmtLog->bindParam(':vid', $visitorId);
        $stmtLog->bindParam(':reason', $reason);
        
        $saved = $stmtLog->execute();
        if ($saved) {
            AuditLog::record($this->conn, 'entrada', 'visitante', (int) $this->conn->lastInsertId(), 'Se registró la entrada del visitante ' . $name . '.', [
                'after' => ['dni' => $dni, 'full_name' => $name, 'company' => $company, 'reason' => $reason]
            ]);
        }
        return $saved;
    }

    // 2. REGISTRAR SALIDA
    public function registerExit($dni) {
        $query = "SELECT l.id, v.nombre_completo AS full_name
                  FROM registros_visitantes l
                  JOIN visitantes v ON l.visitante_id = v.id
                  WHERE v.dni = :dni AND l.hora_salida IS NULL
                  ORDER BY l.id DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':dni', $dni);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $update = "UPDATE registros_visitantes SET hora_salida = NOW() WHERE id = :logId";
            $stmtUp = $this->conn->prepare($update);
            $stmtUp->bindParam(':logId', $row['id']);
            $stmtUp->execute();
            AuditLog::record($this->conn, 'salida', 'visitante', (int) $row['id'], 'Se registró la salida del visitante ' . $row['full_name'] . '.', [
                'after' => ['check_out' => date('Y-m-d H:i:s')]
            ]);
            return "Salida registrada para: " . $row['full_name'];
        } else {
            return false;
        }
    }

    // 3. HISTORIAL CON FILTROS (Esta es la función que te falta)
    public function getHistoryWithFilters($start, $end, $search = "") {
        $query = "SELECT l.id, l.motivo AS reason, l.hora_entrada AS check_in,
                         l.hora_salida AS check_out, v.dni,
                         v.nombre_completo AS full_name, v.empresa AS company
                  FROM registros_visitantes l
                  INNER JOIN visitantes v ON l.visitante_id = v.id
                  WHERE l.hora_entrada BETWEEN :start AND :end";
        
        // Si hay texto de búsqueda, agregamos las condiciones OR
        if (!empty($search)) {
            $query .= " AND (v.nombre_completo LIKE :search OR v.dni LIKE :search OR v.empresa LIKE :search)";
        }
        
        $query .= " ORDER BY l.hora_entrada DESC";
        
        $stmt = $this->conn->prepare($query);
        
        // Concatenamos horas para cubrir todo el día
        $startFull = $start . " 00:00:00";
        $endFull = $end . " 23:59:59";
        
        $stmt->bindParam(':start', $startFull);
        $stmt->bindParam(':end', $endFull);
        
        // Bind de búsqueda
        if (!empty($search)) {
            $searchTerm = "%" . $search . "%";
            $stmt->bindParam(':search', $searchTerm);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>