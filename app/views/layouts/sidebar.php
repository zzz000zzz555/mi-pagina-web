<?php
// =========================================================
// 1. LÓGICA DEL MENÚ (Función para no repetir código)
// =========================================================

// Capturamos controlador y acción para marcar "active"
$c = isset($_GET['c']) ? $_GET['c'] : 'Dashboard';
$a = isset($_GET['a']) ? $_GET['a'] : 'index';

function renderMenu($c, $a) {
?>
    <ul class="list-unstyled">
        
        <li>
            <a href="?c=Dashboard" class="<?php echo ($c=='Dashboard') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <li class="text-muted small fw-bold px-3 mt-3 mb-1">ADMINISTRACIÓN</li>
            
            <li>
                <a href="?c=Employee" class="<?php echo ($c=='Employee') ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i> Estudiantes
                </a>
            </li>
            <li>
                <a href="?c=Department" class="<?php echo ($c=='Department') ? 'active' : ''; ?>">
                    <i class="bi bi-mortarboard-fill"></i> Especialidades
                </a>
            </li>
            <li><a href="?c=Academic&a=cycles" class="<?php echo ($c=='Academic' && $a=='cycles') ? 'active' : ''; ?>"><i class="bi bi-diagram-3"></i> Ciclos</a></li>
            <li><a href="?c=Academic&a=semesters" class="<?php echo ($c=='Academic' && $a=='semesters') ? 'active' : ''; ?>"><i class="bi bi-collection"></i> Semestres</a></li>
            <li><a href="?c=Academic&a=shifts" class="<?php echo ($c=='Academic' && $a=='shifts') ? 'active' : ''; ?>"><i class="bi bi-clock"></i> Turnos</a></li>
            <li>
                <a href="?c=User" class="<?php echo ($c=='User') ? 'active' : ''; ?>">
                    <i class="bi bi-person-gear"></i> Usuarios Sistema
                </a>
            </li>
            
            <li class="text-muted small fw-bold px-3 mt-3 mb-1">REPORTES</li>
            <li>
                <a href="?c=Report&a=history" class="<?php echo ($c=='Report' && $a=='history') ? 'active' : ''; ?>">
                    <i class="bi bi-clock-history"></i> Asistencia por fechas
                </a>
            </li>
            <li><a href="?c=Report&a=monthly" class="<?php echo ($c=='Report' && $a=='monthly') ? 'active' : ''; ?>"><i class="bi bi-calendar3"></i> Registro mensual</a></li>
            <li>
                <a href="?c=Audit" class="<?php echo ($c=='Audit') ? 'active' : ''; ?>">
                    <i class="bi bi-activity"></i> Movimientos del sistema
                </a>
            </li>
            <li>
                <a href="?c=Report" class="<?php echo ($c=='Report' && $a!='history') ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-excel"></i> Descargar Excel
                </a>
            </li>
        <?php endif; ?>

        <li class="text-muted small fw-bold px-3 mt-3 mb-1">SEGURIDAD</li>
        
        <li>
            <a href="?c=Incident" class="<?php echo ($c=='Incident') ? 'active' : ''; ?>">
                <i class="bi bi-journal-text"></i> Bitácora
            </a>
        </li>

        <li>
            <a href="?c=VisitorAdmin" class="<?php echo ($c=='VisitorAdmin') ? 'active' : ''; ?>">
                <i class="bi bi-person-vcard-fill"></i> Visitantes
            </a>
        </li>
        
        <li>
            <a href="#" data-bs-toggle="modal" data-bs-target="#sysPassModal" class="text-info">
                <i class="bi bi-key"></i> Cambiar mi Clave
            </a>
        </li>

        <li class="mt-2 border-top border-secondary pt-2">
            <a href="?c=Attendance" target="_blank" class="text-warning">
                <i class="bi bi-qr-code-scan"></i> Abrir Kiosco
            </a>
        </li>
        
        <li>
            <a href="?c=Auth&a=logout" class="text-danger">
                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
            </a>
        </li>
        <li class="mt-2">
            <button type="button" class="theme-toggle" aria-label="Cambiar tema">
                <i class="bi bi-moon-stars-fill" aria-hidden="true"></i><span> Modo nocturno</span>
            </button>
        </li>
    </ul>

    <div class="modal fade text-dark" id="sysPassModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-dark text-white">
            <h5 class="modal-title"><i class="bi bi-shield-lock"></i> Cambiar Mi Contraseña</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form action="?c=User&a=change_own_password" method="POST">
              <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nueva Contraseña</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar Contraseña</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar</button>
              </div>
          </form>
        </div>
      </div>
    </div>
<?php
}
?>

<div class="sidebar d-none d-md-block" style="width:270px;min-height:100vh">
    
    <div class="py-4 px-3 mb-3 d-flex align-items-center gap-2 border-bottom border-secondary">
        <img src="assets/img/logo-jsc-hd.png" class="brand-logo" alt="Logo IES José Santos Chocano">
        <div>
            <h6 class="m-0 fw-bold">IES José Santos Chocano</h6>
            <small class="text-white-50 text-uppercase" style="font-size: 0.7rem;">
                <?php echo ($_SESSION['role'] ?? '') === 'admin' ? 'Administrador' : 'Vigilante'; ?>
            </small>
        </div>
    </div>
    
    <?php renderMenu($c, $a); ?>
</div>

<div class="d-md-none w-100">
    <nav class="navbar navbar-dark bg-dark mb-3 shadow p-3">
        <div class="container-fluid p-0">
            <button class="btn btn-outline-secondary border-0 text-white" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                <i class="bi bi-list fs-1"></i>
            </button>
            
            <span class="navbar-brand m-0 fw-bold text-white">
                <img src="assets/img/logo-jsc-hd.png" class="brand-logo me-2" alt="Logo"> Control de acceso
            </span>

            <div class="d-flex">
               <a href="?c=Auth&a=logout" class="text-danger fs-2">
                   <i class="bi bi-box-arrow-right"></i>
               </a>
            </div>
        </div>
    </nav>
</div>

<div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="mobileSidebar" style="width: 280px;">
  <div class="offcanvas-header bg-black bg-gradient border-bottom border-secondary">
    <h5 class="offcanvas-title fw-bold">
        <i class="bi bi-person-circle me-2"></i> 
        <?php echo ($_SESSION['role'] ?? '') === 'admin' ? 'Administrador' : 'Vigilante'; ?>
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0 sidebar">
    <?php renderMenu($c, $a); ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const urlParamsSidebar = new URLSearchParams(window.location.search);
    if(urlParamsSidebar.get('msg') === 'pass_ok') Swal.fire('¡Éxito!', 'Tu contraseña ha sido actualizada.', 'success');
    if(urlParamsSidebar.get('err') === 'pass_mismatch') Swal.fire('Error', 'Las contraseñas no coinciden.', 'error');
</script>
