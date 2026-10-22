<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="js/theme.js"></script>
    <link rel="stylesheet" href="css/app.css?v=20261011-1">
    
    <style>
        /* Estilos del Sidebar para que coincidan con el layout */
        .sidebar { min-height: 100vh; background-color: #212529; color: white; }
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 12px 20px; display: block; border-left: 3px solid transparent; transition: 0.3s; }
        .sidebar a:hover { background-color: #343a40; color: white; }
        .sidebar a.active { background-color: #0d6efd; color: white; border-left-color: white; }
        .sidebar i { width: 25px; }
    </style>
</head>
<body>

<div class="d-flex">
    
    <?php require_once '../app/views/layouts/sidebar.php'; ?>

    <div class="flex-grow-1 bg-light" style="height: 100vh; overflow-y: auto;">
        <nav class="navbar navbar-light bg-white shadow-sm px-4 py-3">
            <div class="container-fluid">
                <div><span class="navbar-brand mb-0 h1 fw-bold">Panel institucional</span><div class="small text-muted">IES José Santos Chocano · Huaral</div></div>
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-circle fs-4 text-secondary"></i>
                </div>
            </div>
        </nav>

        <div class="container-fluid p-4">
            
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-0 d-flex align-items-center">
                            <div class="bg-primary text-white p-4"><i class="bi bi-people fs-1"></i></div>
                            <div class="p-4 flex-grow-1">
                                <h6 class="text-muted text-uppercase fw-bold mb-1">Total Estudiantes</h6>
                                <h2 class="mb-0 fw-bold text-dark">
                                    <?php echo isset($totalEmployees) ? $totalEmployees : 0; ?>
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-0 d-flex align-items-center">
                            <div class="bg-success text-white p-4"><i class="bi bi-check-circle fs-1"></i></div>
                            <div class="p-4 flex-grow-1">
                                <h6 class="text-muted text-uppercase fw-bold mb-1">Asistencias Hoy</h6>
                                <h2 class="mb-0 fw-bold text-dark">
                                    <?php echo isset($attendanceToday) ? $attendanceToday : 0; ?>
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-0 d-flex align-items-center">
                            <div class="bg-warning text-white p-4 d-flex align-items-center justify-content-center" style="width: 100px;">
                                <i class="bi bi-clock-history fs-1"></i>
                            </div>
                            
                            <div class="p-4 flex-grow-1">
                                <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.85rem; letter-spacing: 1px;">
                                    Llegadas Tarde
                                </h6>
                                
                                <h2 class="mb-2 fw-bold text-dark display-6">
                                    <?php echo isset($totalLates) ? $totalLates : 0; ?>
                                </h2>

                                <div>
                                    <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 fw-normal">
                                        <i class="bi bi-exclamation-circle me-1"></i>
                                        Según el horario de cada turno
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card shadow border-0 h-100">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold text-secondary"><i class="bi bi-bar-chart-line-fill"></i> Asistencia Semanal</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="attendanceChart" style="max-height: 300px;"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card shadow border-0 h-100">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold text-secondary"><i class="bi bi-list-check"></i> Recientes</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0 small">
                                    <tbody>
                                        <?php if(isset($recentLogs) && count($recentLogs) > 0): ?>
                                            <?php foreach(array_slice($recentLogs, 0, 5) as $log): ?>
                                                <tr>
                                                    <td class="ps-3 fw-bold"><?php echo $log['first_name']; ?></td>
                                                    <td class="text-end pe-3">
                                                        <?php if($log['check_out_time']): ?>
                                                            <span class="badge bg-secondary">Salida <?php echo date('H:i', strtotime($log['check_out_time'])); ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">Entrada <?php echo date('H:i', strtotime($log['check_in_time'])); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td class="text-center py-3 text-muted">Sin registros hoy.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctx = document.getElementById('attendanceChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Asistencias',
                data: <?php echo json_encode($dataValues); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            plugins: { legend: { display: false } }
        }
    });
</script>

</body>
</html>
