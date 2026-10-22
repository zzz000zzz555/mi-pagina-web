<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Importar estudiantes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="js/theme.js"></script>
    <link rel="stylesheet" href="css/app.css?v=20261009-2">
</head>
<body class="bg-light">
<div class="d-flex">
    <?php require_once '../app/views/layouts/sidebar.php'; ?>
    <main class="flex-grow-1 p-3 p-lg-4" style="height:100vh;overflow-y:auto">
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="?c=Employee" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
            <div><h1 class="h2 fw-bold mb-1">Registrar estudiantes masivamente</h1><p class="text-muted mb-0">Importa varios registros con un archivo compatible con Excel.</p></div>
        </div>

        <?php if (!empty($_GET['err'])): ?>
            <div class="alert alert-danger">La importación no pudo completarse. Vuelve a subir el archivo y confirma los datos antes de importar.</div>
        <?php endif; ?>
        <?php if (!empty($importError)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($importError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-xl-5">
                <section class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Formato de registro</h2>
                        <p>Descarga la plantilla y registra los datos personales. Especialidad, ciclo, semestre, sexo y turno se eligen desde listas desplegables dentro del Excel.</p>
                        <a href="?c=Employee&a=importTemplate" class="btn btn-outline-success mb-3">
                            <i class="bi bi-download me-1"></i> Descargar formato de registro
                        </a>
                    </div>
                </section>

                <section class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold"><i class="bi bi-upload text-primary me-2"></i>Subir archivo</h2>
                        <p class="small text-muted">La plantilla reconoce las opciones seleccionadas en Excel. Completa una fila por estudiante; máximo 1,000 filas y 5 MB por archivo.</p>
                        <form action="?c=Employee&a=importCsv" method="POST" enctype="multipart/form-data">
                            <label for="importFile" class="form-label fw-semibold">Selecciona el archivo de registro</label>
                            <input id="importFile" name="import_file" type="file" class="form-control mb-3" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" required>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Revisar archivo</button>
                        </form>
                    </div>
                </section>
            </div>

            <div class="col-xl-7">
                <?php if (!empty($preview)): ?>
                    <section class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold"><i class="bi bi-clipboard-check text-primary me-2"></i>Revisión antes de importar</h2>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge text-bg-primary"><?php echo count($preview['valid_rows']); ?> listos para importar</span>
                                <span class="badge text-bg-danger"><?php echo count($preview['invalid_rows']); ?> con errores</span>
                                <span class="badge text-bg-secondary"><?php echo (int)$preview['total']; ?> filas revisadas</span>
                            </div>
                            <p>Al continuar, se crearán los registros válidos. Las filas con errores se omitirán. El código del carnet se generará automáticamente y cada estudiante tendrá la contraseña inicial <strong>123456</strong>.</p>

                            <?php if ($preview['valid_rows']): ?>
                                <div class="table-responsive mb-3" style="max-height:320px">
                                    <table class="table table-sm table-striped align-middle">
                                        <thead class="table-light"><tr><th>Fila</th><th>DNI</th><th>Estudiante</th><th>Correo</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($preview['valid_rows'] as $row): ?>
                                            <tr><td>Válida</td><td><?php echo htmlspecialchars($row['dni'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                            <?php if ($preview['invalid_rows']): ?>
                                <div class="table-responsive mb-3" style="max-height:260px">
                                    <table class="table table-sm table-bordered align-middle">
                                        <thead class="table-light"><tr><th>Fila</th><th>DNI</th><th>Motivo</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($preview['invalid_rows'] as $row): ?>
                                            <tr><td><?php echo (int)$row['line']; ?></td><td><?php echo htmlspecialchars($row['dni'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars(implode(' ', $row['errors']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex flex-wrap gap-2">
                                <?php if ($preview['valid_rows']): ?>
                                    <form action="?c=Employee&a=importCsv" method="POST" onsubmit="return confirm('¿Confirmas registrar <?php echo count($preview['valid_rows']); ?> estudiantes válidos?');">
                                        <input type="hidden" name="import_token" value="<?php echo htmlspecialchars($preview['token'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Aceptar e importar <?php echo count($preview['valid_rows']); ?></button>
                                    </form>
                                <?php endif; ?>
                                <a href="?c=Employee&a=importCsv" class="btn btn-outline-secondary">Cancelar y subir otro archivo</a>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
