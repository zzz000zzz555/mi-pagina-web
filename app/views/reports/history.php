<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Estudiantes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="js/theme.js"></script>
    <link rel="stylesheet" href="css/app.css?v=20261011-1">
    <link rel="stylesheet" href="css/access-modern.css?v=20261015-1">
    
    <style>
        /* Estilos necesarios para que el Sidebar Maestro se vea bien */
        .sidebar { min-height: 100vh; background-color: #212529; color: white; }
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 12px 20px; display: block; border-left: 3px solid transparent; transition: 0.3s; }
        .sidebar a:hover { background-color: #343a40; color: white; }
        .sidebar a.active { background-color: #0d6efd; color: white; border-left-color: white; }
        .sidebar i { width: 25px; }
        @media print {
            .sidebar, .no-print { display: none !important; }
            .flex-grow-1 { height: auto !important; overflow: visible !important; padding: 0 !important; }
            body { background: #fff !important; font-size: 11px; }
            .card { box-shadow: none !important; border: 0 !important; }
            .table-dark th { background: #263238 !important; color: #fff !important; }
        }
        .history-filter .card-body{padding:1.25rem!important}
        .filter-grid{display:block}.filter-group{display:grid;gap:1rem;align-items:start}.filter-group + .filter-group{margin-top:1rem;padding-top:1rem;border-top:1px solid rgba(140,165,183,.25)}
        .filter-group-academic{grid-template-columns:2fr repeat(4,1fr)}.filter-group-period{grid-template-columns:repeat(3,1fr) 1.15fr}
        .filter-section-title{grid-column:1/-1;margin:0;padding-bottom:.1rem;font-size:.68rem;font-weight:800;letter-spacing:.08em;color:#6f93a9;text-transform:uppercase}
        .filter-field{min-width:0}.filter-field label{display:block;margin-bottom:.42rem;font-size:.72rem;letter-spacing:.035em;text-transform:uppercase;color:#9eb4c5}.filter-field .form-text{min-height:1.05rem;margin-top:.3rem;font-size:.72rem}
        .filter-student,.filter-compact,.filter-date{min-width:0}.filter-actions{display:grid;grid-template-columns:1fr 1fr;gap:.65rem;padding-top:1.42rem}.filter-actions .btn{min-height:44px;white-space:nowrap}
        .history-filter-footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;border-top:1px solid rgba(140,165,183,.25);margin-top:1.15rem;padding-top:1rem}.history-filter-footer small{color:#8da5b8}
        @media(max-width:1199px){.filter-group-academic{grid-template-columns:repeat(2,1fr)}.filter-student{grid-column:span 2}.filter-group-period{grid-template-columns:repeat(2,1fr)}.filter-actions{grid-column:span 2}}
        @media(max-width:767px){.filter-group-academic,.filter-group-period{grid-template-columns:1fr}.filter-student,.filter-actions{grid-column:span 1}.filter-actions{margin-top:.2rem}.history-filter-footer{align-items:stretch;flex-direction:column}.history-filter-footer .btn{width:100%}}
    </style>
</head>
<body class="bg-light">

<div class="d-flex">
    
    <?php require_once '../app/views/layouts/sidebar.php'; ?>

    <div class="flex-grow-1 p-4 history-page" style="height: 100vh; overflow-y: auto;">
        <div class="history-hero mb-4">
            <div><h2 class="mb-1 fw-bold"><i class="bi bi-clock-history me-2"></i>Historial de asistencia</h2><div class="text-muted"><?php echo htmlspecialchars(date('d/m/Y', strtotime($start_date)) . ' — ' . date('d/m/Y', strtotime($end_date)), ENT_QUOTES, 'UTF-8'); ?></div></div>
            <button type="button" onclick="window.print()" class="btn btn-outline-dark no-print"><i class="bi bi-printer me-1"></i> Imprimir</button>
        </div>

        <?php if ($rangeError): ?><div class="alert alert-warning no-print" role="alert">El rango de fechas no es válido. Se muestra el periodo del mes actual.</div><?php endif; ?>
        <div class="card history-filter mb-4 border-0 no-print">
            <div class="card-body bg-white rounded">
                <form action="" method="GET">
                    <input type="hidden" name="c" value="Report">
                    <input type="hidden" name="a" value="history">
                    <div class="filter-grid">
                    <div class="filter-group filter-group-academic"><div class="filter-section-title">Filtros académicos</div>
                        <div class="filter-field filter-student"><label>Estudiante</label><input id="studentSearch" class="form-control" list="studentOptions" placeholder="Escribe para buscar y seleccionar" autocomplete="off"><datalist id="studentOptions"><?php foreach($students as $student): ?><option value="<?php echo htmlspecialchars($student['label'], ENT_QUOTES, 'UTF-8'); ?>" data-id="<?php echo (int)$student['id']; ?>"></option><?php endforeach; ?></datalist><input type="hidden" name="employee_id" id="employeeId" value="<?php echo htmlspecialchars($filters['employee_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><div class="form-text">Busca por nombre o DNI.</div></div>
                        <div class="filter-field filter-compact"><label>Ciclo</label><select name="cycle_id" class="form-select"><option value="">Todos los ciclos</option><?php foreach($cycles as $cycle): ?><option value="<?php echo (int)$cycle['id']; ?>" <?php echo (string)$filters['cycle_id'] === (string)$cycle['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cycle['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
                        <div class="filter-field filter-compact"><label>Semestre</label><select name="semester_id" class="form-select"><option value="">Todos</option><?php foreach($semesters as $semester): ?><option value="<?php echo (int)$semester['id']; ?>" <?php echo (string)$filters['semester_id'] === (string)$semester['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($semester['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
                        <div class="filter-field filter-compact"><label>Turno</label><select name="shift" class="form-select"><option value="">Todos</option><option value="manana" <?php echo $filters['shift'] === 'manana' ? 'selected' : ''; ?>>Mañana</option><option value="tarde" <?php echo $filters['shift'] === 'tarde' ? 'selected' : ''; ?>>Tarde</option></select></div>
                        <div class="filter-field filter-compact"><label>Especialidad</label><select name="department_id" class="form-select"><option value="">Todas</option><?php foreach($departments as $dept): ?><option value="<?php echo (int)$dept['id']; ?>" <?php echo (string)$filters['department_id'] === (string)$dept['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
                    </div>
                    <div class="filter-group filter-group-period"><div class="filter-section-title">Periodo de consulta</div>
                        <div class="filter-field filter-date"><label>Desde</label><input type="date" name="start" class="form-control" value="<?php echo $start_date; ?>"></div>
                        <div class="filter-field filter-date"><label>Hasta</label><input type="date" name="end" class="form-control" value="<?php echo $end_date; ?>"></div>
                        <div class="filter-field filter-date"><label>Día específico</label><input type="date" name="specific_date" class="form-control" value="<?php echo htmlspecialchars($_GET['specific_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><div class="form-text">Opcional: reemplaza el rango.</div></div>
                        <div class="filter-actions"><a href="?c=Report&a=history" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i> Limpiar</a><button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-filter me-1"></i> Aplicar</button></div>
                    </div>
                    </div>
                </form>
<div class="history-filter-footer"><small><i class="bi bi-info-circle me-1"></i>Se muestran hasta <?= (int)$historyLimit ?> filas para agilizar la consulta. Excel exporta todos los resultados filtrados; puedes seleccionar filas para exportar solo esas.</small><form action="?c=Report&a=export" method="POST" id="exportAttendanceForm">
                    <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php foreach($filters as $key => $value): ?><input type="hidden" name="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"><?php endforeach; ?>
                    <button type="submit" class="btn btn-success" id="exportAttendanceButton"><i class="bi bi-file-earmark-excel me-1"></i> Exportar a Excel</button>
                </form>
                </div>
            </div>
        </div>

        <div class="mb-2 text-end no-print">
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger me-2">Tardanza según turno</span>
            <span class="badge bg-success bg-opacity-10 text-success border border-success">Puntual</span>
        </div>

        <div class="card history-table border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-3" style="width:45px"><input class="form-check-input" type="checkbox" id="selectAllAttendance" aria-label="Seleccionar todos los registros visibles"></th>
                                <th>Estudiante</th>
                                <th>DNI</th>
                                <th>Carrera</th>
                                <th>Ciclo</th>
                                <th>Semestre</th>
                                <th>Fecha</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Movimientos</th>
                                <th>Motivo de salida</th>
                                <th>Tiempo Trabajado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($logs) > 0): ?>
                                <?php foreach($logs as $log): ?>
                                
                                <?php 
                                    $esTarde = false;
                                    // Usamos la hora oficial pasada desde el controlador
                                    if($log['status'] === 'tarde') {
                                        $esTarde = true;
                                    }
                                ?>

                                <tr>
                                    <td class="ps-3"><input class="form-check-input attendance-check" type="checkbox" value="<?php echo (int)$log['attendance_id']; ?>" aria-label="Seleccionar registro de <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                                    <td>
                                        <div class="fw-bold"><?php echo $log['first_name'] . ' ' . $log['last_name']; ?></div>
                                        <small class="text-muted"><?php echo $log['employee_code']; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['dni'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($log['department'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($log['cycle_name'] ?? 'Sin asignar', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($log['semester_name'] ?? 'Sin asignar', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($log['date_log'])); ?></td>
                                    
                                    <td>
                                        <?php if($esTarde): ?>
                                            <div class="text-danger fw-bold">
                                                <?php echo date('H:i:s', strtotime($log['check_in_time'])); ?>
                                                <i class="bi bi-exclamation-circle-fill" title="Tarde"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-success fw-bold">
                                                <?php echo date('H:i:s', strtotime($log['check_in_time'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-secondary fw-bold">
                                        <?php if($log['check_out_time']): ?>
                                            <?php echo date('H:i:s', strtotime($log['check_out_time'])); ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">En Turno</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small"><?php echo htmlspecialchars($log['movimientos'] ?? 'Registro anterior sin detalle', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="small"><?php echo htmlspecialchars($log['motivos_salida'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>

                                    <td>
                                        <?php 
                                            if($log['check_out_time']) {
                                                $in = new DateTime($log['check_in_time']);
                                                $out = new DateTime($log['check_out_time']);
                                                $diff = $in->diff($out);
                                                echo '<span class="badge bg-info text-dark fs-6">' . $diff->format('%H h %I m') . '</span>';
                                            } else {
                                                echo "--";
                                            }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="12" class="text-center p-5 text-muted">No se encontraron registros con los filtros seleccionados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('studentSearch')?.addEventListener('change',function(){const option=[...document.querySelectorAll('#studentOptions option')].find(item=>item.value===this.value);document.getElementById('employeeId').value=option?.dataset.id||''});
const attendanceChecks=[...document.querySelectorAll('.attendance-check')],selectAllAttendance=document.getElementById('selectAllAttendance'),exportAttendanceForm=document.getElementById('exportAttendanceForm'),exportAttendanceButton=document.getElementById('exportAttendanceButton');
function updateAttendanceSelection(){const selected=attendanceChecks.filter(check=>check.checked);if(selectAllAttendance){selectAllAttendance.checked=attendanceChecks.length>0&&selected.length===attendanceChecks.length;selectAllAttendance.indeterminate=selected.length>0&&selected.length<attendanceChecks.length}if(exportAttendanceButton)exportAttendanceButton.innerHTML='<i class="bi bi-file-earmark-excel me-1"></i> Exportar '+(selected.length?selected.length+' seleccionado'+(selected.length===1?'':'s'):'a Excel')}
selectAllAttendance?.addEventListener('change',()=>{attendanceChecks.forEach(check=>check.checked=selectAllAttendance.checked);updateAttendanceSelection()});attendanceChecks.forEach(check=>check.addEventListener('change',updateAttendanceSelection));
exportAttendanceForm?.addEventListener('submit',()=>{exportAttendanceForm.querySelectorAll('input[data-selection="attendance"]').forEach(input=>input.remove());attendanceChecks.filter(check=>check.checked).forEach(check=>{const input=document.createElement('input');input.type='hidden';input.name='attendance_ids[]';input.value=check.value;input.dataset.selection='attendance';exportAttendanceForm.appendChild(input)})});updateAttendanceSelection();
</script>
</body>
</html>
