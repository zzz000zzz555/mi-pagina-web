<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kiosco de Control</title>
    
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#c91d2e"><link rel="apple-touch-icon" href="assets/img/logo-jsc-hd.png"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/app.css?v=20261011-1">
    <link rel="stylesheet" href="css/access-modern.css?v=20261015-1">
    
    <style>
        /* Fondo oscuro para ahorrar batería y verse moderno */
        body { background-color: #071521; overflow-x: hidden; }
        
        /* Ajustes de la cámara */
        #reader { width: 100%; border-radius: 10px; overflow: hidden; background-color: #000; }
        #reader video { object-fit: cover; width: 100% !important; border-radius: 10px; }
        
        /* Botones táctiles más grandes */
        .nav-link { font-size: 1.1rem; }

        .kiosk-layout {
            width: min(1160px, 100%);
            display: grid;
            grid-template-columns: minmax(340px, 390px) minmax(430px, 630px);
            align-items: start;
            justify-content: center;
            gap: 32px;
        }
        .kiosk-column { width: 100%; }
        .kiosk-brand { padding-top: 4px; }
        .kiosk-brand h3 { margin-bottom: 3px; font-size: 1.55rem; }
        .live-panel {
            overflow: hidden;
            margin-top: 160px;
            border: 1px solid rgba(104, 183, 222, .32);
            border-radius: 18px;
            background: linear-gradient(145deg, rgba(19, 49, 71, .98), rgba(10, 31, 47, .98));
            box-shadow: 0 20px 46px rgba(0, 0, 0, .28);
        }
        .live-panel__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 22px;
            border-bottom: 1px solid rgba(135, 181, 209, .2);
            color: #f7fbff;
        }
        .live-panel__header h2 { margin: 0; font-size: 1.08rem; font-weight: 750; }
        .live-panel__header p { margin: 3px 0 0; color: #9fc4db; font-size: .78rem; }
        .live-badge { color: #bff5d2; font-size: .74rem; font-weight: 700; }
        .live-badge::before { content: ''; display: inline-block; width: 8px; height: 8px; margin-right: 6px; border-radius: 50%; background: #23c87b; box-shadow: 0 0 0 4px rgba(35,200,123,.14); }
        .live-table { width: 100%; margin: 0; color: #eaf4fb; border-collapse: collapse; }
        .live-table th { padding: 11px 16px; background: rgba(4, 18, 30, .44); color: #9fc4db; font-size: .67rem; letter-spacing: .06em; text-transform: uppercase; white-space: nowrap; }
        .live-table td { padding: 12px 16px; border-top: 1px solid rgba(135,181,209,.13); font-size: .82rem; vertical-align: middle; }
        .live-table tbody tr:nth-child(even) td { background: rgba(3, 20, 33, .16); }
        .live-table tr:hover td { background: rgba(67, 151, 192, .13); }
        .live-table td:first-child { width: 72px; color: #c6e8f6; font-variant-numeric: tabular-nums; font-weight: 700; }
        .live-table td:last-child { width: 142px; }
        .live-student { display: block; max-width: 275px; overflow: hidden; color: #fff; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
        .live-code { display: block; margin-top: 2px; color: #9fc4db; font-size: .69rem; }
        .movement-tag { display: inline-block; padding: 4px 8px; border-radius: 99px; font-size: .68rem; font-weight: 750; white-space: nowrap; }
        .movement-entrada { color: #a9f1c8; background: rgba(24, 164, 94, .2); }
        .movement-reingreso { color: #aee4fb; background: rgba(30, 143, 199, .2); }
        .movement-salida_temporal { color: #ffe19a; background: rgba(213, 141, 10, .22); }
        .movement-salida_final { color: #ffc0c6; background: rgba(206, 52, 67, .22); }
        .live-empty { padding: 42px 20px; color: #9fc4db; text-align: center; font-size: .86rem; }
        .activity-enter { animation: activityEnter .45s ease-out both; }
        .activity-leave { animation: activityLeave .32s ease-in both; }
        @keyframes activityEnter { from { opacity: 0; transform: translateX(18px); background: rgba(42, 188, 125, .25); } to { opacity: 1; transform: translateX(0); background: transparent; } }
        @keyframes activityLeave { to { opacity: 0; transform: translateX(-18px); max-height: 0; padding-top: 0; padding-bottom: 0; } }
        @media (max-width: 900px) {
            .kiosk-layout { grid-template-columns: minmax(0, 440px); justify-content: center; }
            .live-panel { max-width: 440px; margin-top: 0; }
        }
    </style>
</head>
<body class="access-kiosk">

<div class="container min-vh-100 d-flex align-items-start justify-content-center py-4">
    <div class="kiosk-layout">
    <div class="kiosk-column kiosk-shell">
        
        <div class="text-center text-white mb-4 kiosk-brand">
            <img src="assets/img/logo-jsc-hd.png" alt="Logo" style="width:78px;height:78px;object-fit:contain" class="mb-2">
            <h3>Control de acceso</h3>
            <p class="text-white-50 small mb-0">IES José Santos Chocano · Huaral</p>
        </div>

        <?php 
            $msg = isset($_GET['msg']) ? $_GET['msg'] : (isset($message) ? $message : '');
            $err = isset($_GET['err']) ? $_GET['err'] : (isset($error) ? $error : '');
        ?>
        
        <?php if(!empty($msg)): ?>
            <div class="alert alert-success fw-bold text-center shadow mb-3">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $msg; ?>
            </div>
        <?php endif; ?>
        <?php if(!empty($err)): ?>
            <div class="alert alert-danger fw-bold text-center shadow mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $err; ?>
            </div>
        <?php endif; ?>
        <div id="kioskFeedback" class="d-none alert fw-bold text-center shadow mb-3" role="status" aria-live="polite"></div>

        <div class="card kiosk-panel border-0">
            
            <div class="card-header bg-white p-0 border-bottom-0">
                <ul class="nav nav-tabs nav-fill" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-3 fw-bold border-0 border-bottom border-3 border-primary rounded-0" id="employee-tab" data-bs-toggle="tab" data-bs-target="#employee-pane" type="button">
                            <i class="bi bi-qr-code-scan"></i> ESTUDIANTES
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 fw-bold border-0 text-secondary" id="visitor-tab" data-bs-toggle="tab" data-bs-target="#visitor-pane" type="button">
                            <i class="bi bi-person-vcard"></i> VISITANTES
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-3 p-md-4">
                <div class="tab-content" id="myTabContent">
                    
                    <div class="tab-pane fade show active" id="employee-pane">
                        <div class="schedule-strip"><div class="schedule-chip">Mañana<strong><?= htmlspecialchars($schedules['manana']['entrada'] ?? '08:00') ?>–<?= htmlspecialchars($schedules['manana']['salida'] ?? '13:00') ?></strong></div><div class="schedule-chip">Tarde<strong><?= htmlspecialchars($schedules['tarde']['entrada'] ?? '14:00') ?>–<?= htmlspecialchars($schedules['tarde']['salida'] ?? '19:00') ?></strong></div></div>
                        <div class="scan-frame mb-3">
                            <div id="reader"></div>
                        </div>
                        
                        <form action="?c=Attendance&a=register" method="POST" id="attendanceForm">
                            <div class="btn-group w-100 mb-3 action-selector" role="group">
                                <input type="radio" class="btn-check" name="action_type" id="a_in" value="entrada" checked onchange="toggleExitReason()">
                                <label class="btn btn-outline-success" for="a_in"><i class="bi bi-box-arrow-in-right"></i> Entrada / retorno</label>
                                <input type="radio" class="btn-check" name="action_type" id="a_temp_out" value="salida_temporal" onchange="toggleExitReason()">
                                <label class="btn btn-outline-warning" for="a_temp_out"><i class="bi bi-box-arrow-right"></i> Salida temporal</label>
                                <input type="radio" class="btn-check" name="action_type" id="a_final_out" value="salida_final" onchange="toggleExitReason()">
                                <label class="btn btn-outline-danger" for="a_final_out"><i class="bi bi-door-open"></i> Salida final</label>
                            </div>
                            <div id="exitReasonGroup" class="mb-3 d-none">
                                <label class="form-label fw-bold small text-muted">MOTIVO DE SALIDA</label>
                                <input type="text" name="exit_reason" id="exit_reason" class="form-control" maxlength="300" placeholder="Ejemplo: atención médica, refrigerio, trámite familiar">
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" name="employee_code" id="employee_code" 
                                       class="form-control text-center fw-bold fs-4" 
                                       placeholder="Código" autofocus>
                                <label>Código institucional</label>
                            </div>
                            <button type="submit" class="btn btn-register text-white w-100 btn-lg py-3 fw-bold">
                                Registrar
                            </button>
                        </form>
                        <p class="text-center text-muted small mt-3 mb-0">Escanea o escribe un código. El kiosco quedará listo para el siguiente estudiante.</p>
                    </div>

                    <div class="tab-pane fade" id="visitor-pane">
                        <form action="?c=Visitor&a=register" method="POST">
                            <div class="btn-group w-100 mb-3" role="group">
                                <input type="radio" class="btn-check" name="action_type" id="v_in" value="entrada" checked onchange="toggleVisitorFields()">
                                <label class="btn btn-outline-success" for="v_in"><i class="bi bi-box-arrow-in-right"></i> Entrada</label>
                                <input type="radio" class="btn-check" name="action_type" id="v_out" value="salida" onchange="toggleVisitorFields()">
                                <label class="btn btn-outline-danger" for="v_out"><i class="bi bi-box-arrow-right"></i> Salida</label>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted small">IDENTIFICACIÓN</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                                    <input type="number" name="dni" class="form-control form-control-lg" required placeholder="DNI / Cédula">
                                </div>
                            </div>

                            <div id="visitorDetails">
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted small">NOMBRE COMPLETO</label>
                                    <input type="text" name="full_name" class="form-control" placeholder="Nombre del visitante">
                                </div>
                                <div class="row">
                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label fw-bold text-muted small">EMPRESA</label>
                                        <input type="text" name="company" class="form-control" placeholder="Particular">
                                    </div>
                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label fw-bold text-muted small">MOTIVO</label>
                                        <input type="text" name="reason" class="form-control" placeholder="Reunión...">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-dark w-100 py-3 fw-bold shadow mt-2">
                                Procesar Registro
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light text-center py-3">
                <small class="text-muted"><a href="?c=Auth&a=login" class="text-decoration-none fw-bold">Acceso administrativo</a></small>
            </div>
        </div>
    </div>
    <aside class="live-panel" aria-labelledby="liveActivityTitle">
        <div class="live-panel__header">
            <div>
                <h2 id="liveActivityTitle"><i class="bi bi-activity me-2"></i>Actividad reciente</h2>
                <p>Últimas 10 entradas, salidas y retornos</p>
            </div>
            <span class="live-badge">EN VIVO</span>
        </div>
        <div class="table-responsive">
            <table class="live-table" aria-live="polite">
                <thead><tr><th>Hora</th><th>Estudiante</th><th>Movimiento</th></tr></thead>
                <tbody id="liveActivityRows"><tr><td colspan="3" class="live-empty">Cargando actividad reciente…</td></tr></tbody>
            </table>
        </div>
    </aside>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // === 2. REGISTRAR SERVICE WORKER (PWA) ===
    // Esto permite instalar la app en el celular
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js')
                .then(reg => console.log('Service Worker registrado', reg))
                .catch(err => console.log('Service Worker error', err));
        });
    }

    // === 3. LÓGICA DEL ESCÁNER ===
    let isScanning = false;

    function toggleVisitorFields() {
        const isOut = document.getElementById('v_out').checked;
        const detailsDiv = document.getElementById('visitorDetails');
        const inputs = detailsDiv.getElementsByTagName('input');
        if (isOut) {
            detailsDiv.style.display = 'none';
            for(let input of inputs) input.required = false;
        } else {
            detailsDiv.style.display = 'block';
            for(let input of inputs) if(input.name !== 'company') input.required = true;
        }
    }

    function toggleExitReason() {
        const temporaryExit = document.getElementById('a_temp_out').checked;
        const group = document.getElementById('exitReasonGroup');
        const input = document.getElementById('exit_reason');
        group.classList.toggle('d-none', !temporaryExit);
        input.required = temporaryExit;
        if (!temporaryExit) input.value = '';
    }

    function onScanSuccess(decodedText, decodedResult) {
        if (isScanning) return; // Bloquear lecturas múltiples
        isScanning = true;

        html5QrcodeScanner.pause(); // Congelar cámara (Feedback visual)
        
        document.getElementById('employee_code').value = decodedText;
        document.getElementById('attendanceForm').requestSubmit();
    }

    // Configuración optimizada
    let config = { 
        fps: 10, 
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
    };
    
    if (window.innerWidth < 600) {
        config.qrbox = { width: 200, height: 200 };
    }

    let html5QrcodeScanner = new Html5QrcodeScanner("reader", config, false);
    html5QrcodeScanner.render(onScanSuccess);

    function showKioskFeedback(ok, message) {
        const feedback = document.getElementById('kioskFeedback');
        feedback.className = 'alert fw-bold text-center shadow mb-3 ' + (ok ? 'alert-success' : 'alert-danger');
        feedback.innerHTML = '<i class="bi ' + (ok ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill') + ' me-2"></i>' + escapeHtml(message);
    }
    async function resumeScanner() {
        isScanning = false;
        try {
            if (html5QrcodeScanner && typeof html5QrcodeScanner.resume === 'function') html5QrcodeScanner.resume();
        } catch (error) {
            // Algunos navegadores necesitan que el vigilante pulse "Start Scanning" otra vez.
        }
    }
    document.getElementById('attendanceForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        const form = event.currentTarget;
        const codeInput = document.getElementById('employee_code');
        const code = codeInput.value.trim();
        if (!code) {
            showKioskFeedback(false, 'Escanee o escriba el código institucional del estudiante.');
            await resumeScanner();
            return;
        }
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Registrando…';
        try {
            const data = new FormData(form);
            data.append('ajax', '1');
            const response = await fetch(form.action, { method: 'POST', body: data, cache: 'no-store' });
            const result = await response.json();
            showKioskFeedback(Boolean(result.ok), result.message || 'No se pudo procesar el movimiento.');
            if (result.ok) {
                codeInput.value = '';
                loadLiveActivity();
            }
        } catch (error) {
            showKioskFeedback(false, 'No se pudo conectar con el servidor. Intente nuevamente.');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
            codeInput.focus();
            await resumeScanner();
        }
    });

    // Panel lateral: consulta liviana y limitada a diez movimientos.
    let liveFingerprint = '';
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
    }
    function movementInfo(type) {
        const movements = {
            entrada: ['Entrada', 'movement-entrada'],
            reingreso: ['Retorno', 'movement-reingreso'],
            salida_temporal: ['Salida temporal', 'movement-salida_temporal'],
            salida_final: ['Salida final', 'movement-salida_final']
        };
        return movements[type] || ['Movimiento', 'movement-entrada'];
    }
    function displayTime(value) {
        const match = String(value || '').match(/(\d{2}:\d{2})(?::\d{2})?$/);
        return match ? match[1] : '—';
    }
    function createActivityRow(item, animate) {
        const info = movementInfo(item.tipo);
        return `<tr data-movement-id="${item.id}"${animate ? ' class="activity-enter"' : ''}>
            <td>${displayTime(item.fecha_hora)}</td>
            <td><span class="live-student" title="${escapeHtml(item.estudiante)}">${escapeHtml(item.estudiante)}</span><span class="live-code">${escapeHtml(item.codigo_estudiante)}</span></td>
            <td><span class="movement-tag ${info[1]}">${info[0]}</span></td>
        </tr>`;
    }
    function renderLiveActivity(items) {
        const body = document.getElementById('liveActivityRows');
        const fingerprint = items.map(item => item.id).join(',');
        if (fingerprint === liveFingerprint) return;
        const previousIds = new Set(Array.from(body.querySelectorAll('[data-movement-id]')).map(row => String(row.dataset.movementId)));
        const newIds = new Set(items.map(item => String(item.id)));
        const removed = Array.from(body.querySelectorAll('[data-movement-id]')).filter(row => !newIds.has(String(row.dataset.movementId)));
        removed.forEach(row => row.classList.add('activity-leave'));
        const draw = () => {
            if (!items.length) body.innerHTML = '<tr><td colspan="3" class="live-empty">Aún no hay movimientos registrados.</td></tr>';
            else body.innerHTML = items.slice(0, 10).map(item => createActivityRow(item, liveFingerprint !== '' && !previousIds.has(String(item.id)))).join('');
            liveFingerprint = fingerprint;
        };
        if (removed.length && liveFingerprint !== '') window.setTimeout(draw, 300); else draw();
    }
    async function loadLiveActivity() {
        try {
            const response = await fetch('?c=Attendance&a=recent', { cache: 'no-store', headers: { 'Accept': 'application/json' } });
            const payload = await response.json();
            if (payload.ok) renderLiveActivity(payload.data || []);
        } catch (error) {
            // El registro del kiosco sigue funcionando aunque la actualización visual falle de forma temporal.
        }
    }
    loadLiveActivity();
    window.setInterval(loadLiveActivity, 5000);
</script>

</body>
</html>
