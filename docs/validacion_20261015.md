# Validación y reparaciones — 15/10/2026

## Incidencias corregidas

1. Migración de columnas incompatible con MariaDB.
   - Causa: `RENAME COLUMN` no es admitido por la versión instalada.
   - Corrección: se creó y aplicó `database/migrations/20261015_reparar_columnas_mariadb.sql`, usando `CHANGE COLUMN`.
   - Respaldo previo: `database/backups/antes_reparacion_columnas_20261015_160452.sql`.

2. Auditoría exportada con JSON técnico y columnas demasiado extensas.
   - Causa: el exportador imprimía `detalles_json` sin convertirlo.
   - Corrección: el reporte ahora contiene Fecha y hora, Usuario, Acción, Módulo, Descripción y Datos registrados. Los datos se presentan con etiquetas en español.

3. Asistencia sin soporte para salida temporal y reingreso.
   - Corrección: se creó y aplicó `database/migrations/20261015_movimientos_asistencia.sql` y la tabla `movimientos_asistencia`.
   - Incluye horarios: mañana 08:00–13:00 y tarde 14:00–19:00.

4. Filtro de historial por estudiante/DNI con error de parámetros en MariaDB.
   - Causa: se reutilizaba el mismo parámetro preparado para dos condiciones (`nombre` y `DNI`).
   - Corrección: se usan parámetros independientes para cada condición.
   - Validación: la consulta de Kinich devuelve una fila con movimientos y motivo en columnas separadas.

5. Portal de estudiantes innecesario para el control institucional.
   - Corrección: se eliminó el controlador, las vistas, el acceso de estudiantes en el inicio de sesión y las rutas del Portal.
   - Regla operativa: el QR del carnet se registra únicamente desde el Kiosco por un usuario autenticado.

6. Permisos de visualización y operación.
   - Administrador: dashboard, estudiantes, especialidades, usuarios, configuración, reportes, auditoría, incidencias, visitantes y kiosco.
   - Vigilante: dashboard, incidencias, visitantes, cambio de clave y kiosco.
   - El kiosco y el registro de visitantes ahora exigen una sesión con rol `admin` o `guardia`; no pueden utilizarse de forma pública.
   - Se eliminaron los scripts públicos de restablecimiento de contraseña y un enrutador PHP duplicado fuera del punto de entrada oficial.

## Pruebas realizadas

| Componente | Resultado |
|---|---|
| Sintaxis PHP: asistencia, historial, configuración y auditoría | Correcta |
| Consulta de estudiantes desde PHP | Correcta: 14 registros |
| Consulta de usuario administrador | Correcta |
| Claves foráneas con nombres en español | Correctas |
| Kiosco de asistencia | Disponible mediante HTTP 200 |
| Consulta de historial de asistencia | Correcta |
| Movimientos de auditoría existentes | Entrada, salida temporal, reingreso y salida final verificados |
| Conversión de detalle de auditoría | Correcta: se muestran hora, motivo y estudiante sin JSON crudo |
| Eliminación del Portal y vista de inicio de sesión | Correcta: no quedan referencias al Portal |
| Sintaxis PHP de control de roles y autenticación | Correcta |

## Prueba operativa pendiente

La lectura real del QR desde una cámara física debe validarse en el navegador del equipo o teléfono que usará el kiosco. El código y el endpoint están disponibles, pero esa prueba depende del permiso de cámara y del dispositivo.
