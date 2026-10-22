<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo estudiante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4>Agregar nuevo estudiante</h4>
                </div>
                <div class="card-body">
                    <form action="?c=Employee&a=store" method="POST">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código del carnet</label>
                                <input type="text" class="form-control" readonly placeholder="Se usará el número de DNI">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombres</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellidos</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Especialidad</label>
                                <select name="department_id" class="form-select">
                                    <?php foreach($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>">
                                            <?php echo $dept['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ciclo</label>
                                <select name="cycle_id" class="form-select" required>
                                    <option value="">Seleccione</option>
                                    <?php foreach($cycles as $cycle): ?>
                                        <option value="<?php echo (int)$cycle['id']; ?>"><?php echo htmlspecialchars($cycle['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Semestre</label>
                            <select name="semester_id" class="form-select" required>
                                <option value="">Seleccione</option>
                                <?php foreach($semesters as $semester): ?>
                                    <option value="<?php echo (int)$semester['id']; ?>"><?php echo htmlspecialchars($semester['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-success">Guardar estudiante</button>
                        <a href="?c=Employee" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
