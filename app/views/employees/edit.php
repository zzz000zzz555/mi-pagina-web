<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar estudiante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="js/theme.js"></script>
    <link rel="stylesheet" href="css/app.css?v=20261011-1">
    
    <style>
        /* Estilos necesarios para el Sidebar */
        .sidebar { min-height: 100vh; background-color: #212529; color: white; }
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 12px 20px; display: block; border-left: 3px solid transparent; transition: 0.3s; }
        .sidebar a:hover { background-color: #343a40; color: white; }
        .sidebar a.active { background-color: #0d6efd; color: white; border-left-color: white; }
        .sidebar i { width: 25px; }
    </style>
</head>
<body class="bg-light">

<div class="d-flex">
    
    <?php require_once '../app/views/layouts/sidebar.php'; ?>

    <div class="flex-grow-1 p-4" style="height: 100vh; overflow-y: auto;">
        
        <div class="d-flex align-items-center mb-4">
            <a href="?c=Employee" class="btn btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i> Volver</a>
            <h2 class="mb-0 fw-bold text-secondary"><i class="bi bi-pencil-square"></i> Editar estudiante</h2>
        </div>

        <div class="card shadow border-0">
            <?php if(isset($_GET['err'])): ?>
                <div class="alert alert-danger m-3 mb-0">Verifique sus datos personales, el ciclo y el semestre. El DNI no puede estar registrado en otro estudiante.</div>
            <?php endif; ?>
            <div class="card-header bg-warning text-dark fw-bold">
                Actualizar Datos
            </div>
            <div class="card-body p-4">
                <form action="?c=Employee&a=update_data" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $emp['id']; ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Código del carnet (DNI)</label>
                            <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($emp['employee_code'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" required value="<?php echo $emp['email']; ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombres</label>
                            <input type="text" name="first_name" class="form-control" required value="<?php echo $emp['first_name']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Apellidos</label>
                            <input type="text" name="last_name" class="form-control" required value="<?php echo $emp['last_name']; ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Número de DNI</label>
                            <input type="text" name="dni" class="form-control" required maxlength="20" inputmode="numeric" value="<?php echo htmlspecialchars($emp['dni'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Edad</label>
                            <input type="number" name="age" class="form-control" required min="3" max="120" value="<?php echo htmlspecialchars($emp['age'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Sexo</label>
                            <select name="sex" class="form-select" required>
                                <option value="">Seleccione</option>
                                <option value="masculino" <?php echo (($emp['sex'] ?? '') === 'masculino') ? 'selected' : ''; ?>>Masculino</option>
                                <option value="femenino" <?php echo (($emp['sex'] ?? '') === 'femenino') ? 'selected' : ''; ?>>Femenino</option>
                                <option value="otro" <?php echo (($emp['sex'] ?? '') === 'otro') ? 'selected' : ''; ?>>Otro</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Especialidad</label>
                            <select name="department_id" class="form-select">
                                <?php foreach($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($dept['id'] == $emp['department_id']) ? 'selected' : ''; ?>>
                                        <?php echo $dept['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ciclo</label>
                            <select name="cycle_id" class="form-select" required>
                                <option value="">Seleccione</option>
                                <?php foreach($cycles as $cycle): ?>
                                    <option value="<?php echo (int)$cycle['id']; ?>" <?php echo (int)($emp['cycle_id'] ?? 0) === (int)$cycle['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cycle['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($emp['cycle_id']) && !empty($emp['legacy_cycle'])): ?>
                                <div class="form-text text-warning">Valor anterior pendiente de revisar: <?php echo htmlspecialchars($emp['legacy_cycle'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Semestre</label>
                            <select name="semester_id" class="form-select" required>
                                <option value="">Seleccione</option>
                                <?php foreach($semesters as $semester): ?>
                                    <option value="<?php echo (int)$semester['id']; ?>" <?php echo (int)($emp['semester_id'] ?? 0) === (int)$semester['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($semester['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Turno</label>
                            <select name="shift" class="form-select" required>
                                <option value="manana" <?php echo (($emp['shift'] ?? 'manana') === 'manana') ? 'selected' : ''; ?>>Mañana</option>
                                <option value="tarde" <?php echo (($emp['shift'] ?? '') === 'tarde') ? 'selected' : ''; ?>>Tarde</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Fotografía del estudiante</label>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">JPG, PNG o WEBP. Máximo 3 MB. Déjelo vacío para conservar la foto actual.</div>
                        </div>
                    </div>

                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-end">
                        <a href="?c=Employee" class="btn btn-secondary me-2">Cancelar</a>
                        <button type="submit" class="btn btn-warning fw-bold px-4">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
