<?php
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$queryFilters = $filters;
$queryFilters['c'] = 'Audit';
$queryFilters['a'] = 'index';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historial de movimientos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="js/theme.js"></script>
    <link rel="stylesheet" href="css/app.css?v=20261011-1">
    <link rel="stylesheet" href="css/access-modern.css?v=20261015-2">
</head>
<body class="bg-light">
<div class="d-flex">
    <?php require_once '../app/views/layouts/sidebar.php'; ?>
    <main class="flex-grow-1 p-3 p-lg-4 audit-page" style="height:100vh;overflow-y:auto">
        <div class="audit-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="page-title h2 fw-bold mb-1"><i class="bi bi-activity me-2"></i>Historial de movimientos</h1>
                <p class="text-muted mb-0">Registro de altas, ediciones, eliminaciones, cambios de estado y entradas/salidas.</p>
            </div>
            <a class="btn btn-success" href="?c=Audit&amp;a=export&amp;<?= $escape(http_build_query($filters)) ?>">
                <i class="bi bi-file-earmark-excel me-1"></i> Exportar a Excel
            </a>
        </div>
        <?php if ($rangeError): ?>
            <div class="alert alert-warning" role="alert">El rango de fechas no es válido. Se muestra el periodo del mes actual.</div>
        <?php endif; ?>
        <?php if (isset($_GET['err'])): ?>
            <div class="alert alert-danger" role="alert">No se pudo exportar: revisa el rango de fechas.</div>
        <?php endif; ?>

        <section class="card audit-filter border-0 mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="c" value="Audit">
                    <div class="col-sm-6 col-xl-2"><label class="form-label fw-semibold">Desde</label><input type="date" name="start" class="form-control" value="<?= $escape($filters['start']) ?>"></div>
                    <div class="col-sm-6 col-xl-2"><label class="form-label fw-semibold">Hasta</label><input type="date" name="end" class="form-control" value="<?= $escape($filters['end']) ?>"></div>
                    <div class="col-sm-6 col-xl-2"><label class="form-label fw-semibold">Acción</label>
                        <select name="action" class="form-select">
                            <option value="">Todas</option>
                            <?php foreach (['crear', 'editar', 'eliminar', 'cambiar_estado', 'cambiar_clave', 'entrada', 'salida_temporal', 'salida_final', 'reingreso', 'iniciar_sesion', 'cerrar_sesion'] as $action): ?>
                                <option value="<?= $escape($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= $escape(ucfirst(str_replace('_', ' ', $action))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-6 col-xl-2"><label class="form-label fw-semibold">Módulo</label>
                        <select name="entity" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach (['estudiante', 'asistencia', 'visitante', 'especialidad', 'usuario', 'configuracion', 'incidencia', 'sesion'] as $entity): ?>
                                <option value="<?= $escape($entity) ?>" <?= $filters['entity'] === $entity ? 'selected' : '' ?>><?= $escape(ucfirst($entity)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-8 col-xl-3"><label class="form-label fw-semibold">Buscar</label><input type="search" name="search" class="form-control" value="<?= $escape($filters['search']) ?>" placeholder="Actor, descripción o ID"></div>
                    <div class="col-sm-4 col-xl-1 d-flex gap-2"><a href="?c=Audit" class="btn btn-outline-secondary" aria-label="Limpiar filtros"><i class="bi bi-x-lg"></i></a><button class="btn btn-primary flex-grow-1" type="submit" aria-label="Filtrar"><i class="bi bi-funnel"></i></button></div>
                </form>
            </div>
        </section>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted small"><?= number_format($total) ?> movimientos encontrados</span>
            <span class="text-muted small">Página <?= $page ?> de <?= $pageCount ?></span>
        </div>
        <section class="card audit-table border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark"><tr><th>Fecha y hora</th><th>Actor</th><th>Acción</th><th>Módulo</th><th>Descripción</th><th>Detalle</th></tr></thead>
                    <tbody>
                    <?php if ($logs): foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-nowrap"><?= $escape(date('d/m/Y H:i:s', strtotime($log['created_at']))) ?></td>
                            <td><?= $escape($log['actor_name']) ?><small class="d-block text-muted"><?= $escape(ucfirst($log['actor_type'])) ?></small></td>
                            <td><span class="badge text-bg-secondary"><?= $escape($log['action_label']) ?></span></td>
                            <td><?= $escape($log['entity_label']) ?><?= $log['entity_id'] !== null ? ' #' . (int) $log['entity_id'] : '' ?></td>
                            <td><?= $escape($log['description']) ?></td>
                            <td>
                                <span class="small"><?= $escape($log['details_text']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center p-5 text-muted">No hay movimientos para los filtros seleccionados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pageCount > 1): ?>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <?php $previous = $queryFilters; $previous['page'] = max(1, $page - 1); $next = $queryFilters; $next['page'] = min($pageCount, $page + 1); ?>
                    <a class="btn btn-sm btn-outline-secondary <?= $page <= 1 ? 'disabled' : '' ?>" href="?<?= $escape(http_build_query($previous)) ?>"><i class="bi bi-chevron-left"></i> Anterior</a>
                    <span class="small text-muted">Mostrando <?= count($logs) ?> de <?= number_format($total) ?></span>
                    <a class="btn btn-sm btn-outline-secondary <?= $page >= $pageCount ? 'disabled' : '' ?>" href="?<?= $escape(http_build_query($next)) ?>">Siguiente <i class="bi bi-chevron-right"></i></a>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
