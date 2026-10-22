<?php
class AuditLog {
    private static $actorNames = [];

    public static function record(PDO $db, string $action, string $entity, ?int $entityId, string $description, array $details = []): void {
        $actorUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        $actorType = $actorUserId ? 'usuario' : 'sistema';
        $actorName = 'Sistema / Kiosco';

        if ($actorUserId && !empty($_SESSION['username'])) {
            $actorName = (string) $_SESSION['username'];
        } elseif ($actorUserId && isset(self::$actorNames['user:' . $actorUserId])) {
            $actorName = self::$actorNames['user:' . $actorUserId];
        } elseif ($actorUserId) {
            $stmt = $db->prepare('SELECT nombre_usuario FROM usuarios WHERE id = :id');
            $stmt->execute(['id' => $actorUserId]);
            $actorName = (string) ($stmt->fetchColumn() ?: 'Usuario #' . $actorUserId);
            self::$actorNames['user:' . $actorUserId] = $actorName;
        } elseif (!empty($_SESSION['audit_actor_name'])) {
            $actorName = (string) $_SESSION['audit_actor_name'];
            $actorType = 'externo';
        }

        $stmt = $db->prepare(
            'INSERT INTO bitacora_auditoria
                (usuario_actor_id, estudiante_actor_id, tipo_actor, nombre_actor, accion, entidad, id_entidad,
                 descripcion, detalles_json, direccion_ip, agente_usuario)
             VALUES
                (:actor_user_id, :actor_employee_id, :actor_type, :actor_name, :action, :entity, :entity_id,
                 :description, :details_json, :ip_address, :user_agent)'
        );
        $stmt->execute([
            'actor_user_id' => $actorUserId ?: null,
            'actor_employee_id' => null,
            'actor_type' => $actorType,
            'actor_name' => mb_substr($actorName, 0, 150, 'UTF-8'),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'description' => mb_substr($description, 0, 500, 'UTF-8'),
            'details_json' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 255, 'UTF-8') : null,
        ]);
    }
}
