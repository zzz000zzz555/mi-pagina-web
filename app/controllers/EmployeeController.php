<?php
require_once '../app/config/db.php';
require_once '../app/models/Employee.php';

class EmployeeController {
    private $employeeModel;
    private $db;

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        
        // 1. Verificar Login
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?c=Auth&a=login");
            exit;
        }
        
        // 2. Bloqueo de Rol (Solo Admin)
        if ($_SESSION['role'] != 'admin') {
            header("Location: ?c=Dashboard");
            exit;
        }

        $database = new Database();
        $this->db = $database->getConnection();
        $this->employeeModel = new Employee($this->db);
    }

    // 1. LISTAR EMPLEADOS (Y CARGAR DATOS PARA EL MODAL)
    public function index() {
        // Filtros operativos: estudiante/DNI, ciclo, semestre, turno y especialidad.
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'cycle_id' => trim($_GET['cycle_id'] ?? ''),
            'semester_id' => trim($_GET['semester_id'] ?? ''),
            'shift' => trim($_GET['shift'] ?? ''),
            'department_id' => trim($_GET['department_id'] ?? '')
        ];
        
        // A. Obtener lista de empleados
        $employees = $this->employeeModel->read($filters);
        
        // B. Obtener departamentos (Necesario para el Modal de Crear)
        $stmt = $this->db->query("SELECT id, nombre AS name, estado AS status FROM especialidades WHERE estado = 'activo'");
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $cycles = $this->db->query("SELECT id, nombre AS name FROM ciclos ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $semesters = $this->db->query("SELECT id, nombre AS name FROM semestres ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        
        require_once '../app/views/employees/index.php';
    }

    // 2. GUARDAR NUEVO EMPLEADO (Desde el Modal)
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validPersonalData($_POST) || !$this->validAcademicAssignment($_POST)) {
                header("Location: ?c=Employee&err=datos_personales");
                exit;
            }
            $photoPath = $this->savePhoto($_FILES['photo'] ?? null);
            $data = [
                'employee_code' => $this->employeeModel->generateUniqueCode(),
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'dni' => trim($_POST['dni']),
                'age' => (int) $_POST['age'],
                'sex' => $_POST['sex'],
                'email' => $_POST['email'],
                'department_id' => $_POST['department_id'],
                'cycle_id' => (int) $_POST['cycle_id'],
                'semester_id' => (int) $_POST['semester_id'],
                'shift' => $_POST['shift'] ?? 'manana',
                'photo_path' => $photoPath
            ];
            
            // El modelo se encarga de asignar la contraseña por defecto '123456'
            if ($this->employeeModel->create($data)) {
                header("Location: ?c=Employee&msg=guardado");
            } else {
                // Manejo básico de error (opcional)
                header("Location: ?c=Employee&err=error");
            }
        }
    }

    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_token'])) {
            $preview = $_SESSION['employee_import_preview'] ?? null;
            if (!$preview || !hash_equals($preview['token'], (string) $_POST['import_token'])) {
                header("Location: ?c=Employee&a=importCsv&err=preview_expired");
                exit;
            }

            try {
                $this->db->beginTransaction();
                $imported = 0;
                foreach ($preview['valid_rows'] as $row) {
                    if ($this->employeeModel->existsByDni($row['dni'])) {
                        throw new RuntimeException('El DNI ' . $row['dni'] . ' ya fue registrado por otro usuario.');
                    }
                    $row['employee_code'] = $this->employeeModel->generateUniqueCode();
                    $row['photo_path'] = 'default.png';
                    if (!$this->employeeModel->create($row)) {
                        throw new RuntimeException('No se pudo guardar el estudiante con DNI ' . $row['dni'] . '.');
                    }
                    $imported++;
                }
                $this->db->commit();
                unset($_SESSION['employee_import_preview']);
                header("Location: ?c=Employee&msg=importados&count=" . $imported);
                exit;
            } catch (Throwable $error) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                error_log('Student CSV import failed: ' . $error->getMessage());
                unset($_SESSION['employee_import_preview']);
                header("Location: ?c=Employee&a=importCsv&err=import_failed");
                exit;
            }
        }

        $cycles = $this->db->query("SELECT id, nombre AS name FROM ciclos ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $semesters = $this->db->query("SELECT id, nombre AS name FROM semestres ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $departments = $this->db->query("SELECT id, nombre AS name FROM especialidades WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $preview = $_SESSION['employee_import_preview'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            unset($_SESSION['employee_import_preview']);
            $preview = null;
            $file = $_FILES['import_file'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] < 1 || $file['size'] > 5 * 1024 * 1024) {
                $importError = 'Selecciona un archivo Excel (.xlsx) o CSV válido de hasta 5 MB.';
                require_once '../app/views/employees/import.php';
                return;
            }
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
                $importError = 'Formato no admitido. Usa la plantilla Excel (.xlsx) descargada o un archivo CSV UTF-8.';
                require_once '../app/views/employees/import.php';
                return;
            }

            try {
                if ($extension === 'xlsx') {
                    [$records, $headerError] = $this->readStudentXlsx($file['tmp_name']);
                } elseif ($extension === 'xls') {
                    [$records, $headerError] = $this->readStudentExcel($file['tmp_name']);
                } else {
                    [$records, $headerError] = $this->readStudentCsv($file['tmp_name']);
                }
                if ($headerError !== null) {
                    $importError = $headerError;
                } else {
                    $preview = $this->validateStudentImport($records, $departments, $cycles, $semesters);
                    $preview['token'] = bin2hex(random_bytes(24));
                    $_SESSION['employee_import_preview'] = $preview;
                }
            } catch (RuntimeException $error) {
                $importError = $error->getMessage();
            }
        }

        require_once '../app/views/employees/import.php';
    }

    public function importTemplate() {
        if (!class_exists('ZipArchive')) {
            http_response_code(500);
            exit('El servidor requiere la extensión ZIP de PHP para generar la plantilla Excel.');
        }
        $departments = $this->db->query("SELECT id, nombre AS name FROM especialidades WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $cycles = $this->db->query("SELECT id, nombre AS name FROM ciclos ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $semesters = $this->db->query("SELECT id, nombre AS name FROM semestres ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $path = tempnam(sys_get_temp_dir(), 'student_template_');
        if ($path === false || !$this->createStudentXlsxTemplate($path, $departments, $cycles, $semesters)) {
            throw new RuntimeException('No se pudo crear la plantilla de registro.');
        }
        $filename = 'Formato_registro_estudiantes.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        @unlink($path);
        exit;
    }

    private function readStudentExcel($path) {
        if (!class_exists('DOMDocument')) {
            throw new RuntimeException('El servidor no tiene habilitado el lector HTML necesario para procesar la plantilla Excel.');
        }
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('No se pudo leer el archivo Excel seleccionado.');
        }
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $document = new DOMDocument();
        $previousSetting = libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousSetting);
        }
        if (!$loaded) {
            return [[], 'No se pudo leer la hoja Excel. Descarga nuevamente el formato del sistema.'];
        }

        $rows = $document->getElementsByTagName('tr');
        if ($rows->length === 0) {
            return [[], 'El archivo Excel no contiene una tabla de estudiantes. Usa la plantilla descargada.'];
        }
        $headers = [];
        foreach ($rows->item(0)->getElementsByTagName('th') as $header) {
            $headers[] = strtolower(trim($header->textContent));
        }
        $headers = $this->normalizeStudentHeaders($headers);
        if ($headers === null) {
            return [[], 'Los encabezados no corresponden al formato. Conserva la primera fila de la plantilla Excel.'];
        }

        $records = [];
        for ($index = 1; $index < $rows->length; $index++) {
            $cells = $rows->item($index)->getElementsByTagName('td');
            $values = [];
            foreach ($cells as $cell) {
                $values[] = trim($cell->textContent);
            }
            if (!$values || count(array_filter($values, static function ($value) { return $value !== ''; })) === 0) {
                continue;
            }
            if (count($records) >= 1000) {
                throw new RuntimeException('El archivo supera el máximo de 1,000 estudiantes por importación.');
            }
            $values = array_pad($values, count($headers), '');
            $records[] = [
                'line' => $index + 1,
                'values' => array_combine($headers, array_slice($values, 0, count($headers)))
            ];
        }
        if (!$records) {
            return [[], 'La hoja Excel solo contiene encabezados; agrega al menos un estudiante.'];
        }
        return [$records, null];
    }

    private function readStudentCsv($path) {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('No se pudo leer el archivo seleccionado.');
        }
        if (strncmp($content, "\xFF\xFE", 2) === 0) {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        } elseif (strncmp($content, "\xFE\xFF", 2) === 0) {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
        } elseif (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
            $content = substr($content, 3);
        }
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $firstLine = strtok($content, "\r\n");
        if ($firstLine === false || trim($firstLine) === '') {
            return [[], 'El archivo está vacío o no contiene encabezados.'];
        }
        $delimiter = ';';
        $delimiterCount = 0;
        foreach ([';', ',', "\t"] as $candidate) {
            $count = substr_count($firstLine, $candidate);
            if ($count > $delimiterCount) {
                $delimiter = $candidate;
                $delimiterCount = $count;
            }
        }
        if ($delimiterCount === 0) {
            return [[], 'No se reconocen columnas separadas. Usa el formato TSV descargado o un CSV con columnas separadas.'];
        }

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new RuntimeException('No se pudo procesar el archivo seleccionado.');
        }
        fwrite($stream, $content);
        rewind($stream);
        $headers = fgetcsv($stream, 0, $delimiter, '"', '');
        $headers = $this->normalizeStudentHeaders($headers ?: []);
        if ($headers === null) {
            fclose($stream);
            return [[], 'Los encabezados no corresponden al formato. Descarga el formato de registro y no cambies la primera fila.'];
        }

        $records = [];
        $recordNumber = 1;
        while (($values = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            $recordNumber++;
            if ($values === [null] || (count($values) === 1 && trim((string) $values[0]) === '')) continue;
            if (count($records) >= 1000) {
                fclose($stream);
                throw new RuntimeException('El archivo supera el máximo de 1,000 estudiantes por importación.');
            }
            $values = array_pad($values, count($headers), '');
            $records[] = ['line' => $recordNumber, 'values' => array_combine($headers, array_slice($values, 0, count($headers)))];
        }
        fclose($stream);
        if (!$records) return [[], 'El archivo solo contiene encabezados; agrega al menos un estudiante.'];
        return [$records, null];
    }

    private function readStudentXlsx($path) {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('El servidor no tiene habilitada la extensión ZIP necesaria para leer archivos .xlsx.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) return [[], 'No se pudo abrir el archivo .xlsx. Descarga nuevamente el formato del sistema.'];
        try {
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheetXml === false) return [[], 'El archivo .xlsx no contiene la hoja de registro esperada.'];
            $shared = [];
            $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedXml !== false) {
                $xml = simplexml_load_string($sharedXml);
                if ($xml !== false) foreach ($xml->si as $entry) $shared[] = (string) $entry->t ?: implode('', array_map('strval', iterator_to_array($entry->r)));
            }
            $xml = simplexml_load_string($sheetXml);
            if ($xml === false || !isset($xml->sheetData->row)) return [[], 'La hoja de registro no contiene datos.'];
            $rows = [];
            foreach ($xml->sheetData->row as $xmlRow) {
                $values = [];
                foreach ($xmlRow->c as $cell) {
                    $reference = (string) $cell['r'];
                    $column = preg_replace('/[0-9]/', '', $reference);
                    $type = (string) $cell['t'];
                    if ($type === 'inlineStr') $value = (string) $cell->is->t;
                    else $value = (string) $cell->v;
                    if ($type === 's') $value = $shared[(int) $value] ?? '';
                    $values[$column] = trim($value);
                }
                $rows[(int) $xmlRow['r']] = $values;
            }
            $firstRow = reset($rows);
            if (!$firstRow) return [[], 'La hoja de registro no contiene encabezados.'];
            $headers = $this->normalizeStudentHeaders(array_values($firstRow));
            if ($headers === null) return [[], 'Los encabezados no corresponden al formato. Descarga el formato de registro y no cambies la primera fila.'];
            $records = [];
            $headerColumns = array_keys($firstRow);
            $headerLine = array_key_first($rows);
            foreach ($rows as $line => $cells) {
                if ($line === $headerLine) continue;
                $values = [];
                foreach ($headerColumns as $column) $values[] = $cells[$column] ?? '';
                if (count(array_filter($values, static fn($value) => $value !== '')) === 0) continue;
                if (count($records) >= 1000) throw new RuntimeException('El archivo supera el máximo de 1,000 estudiantes por importación.');
                $records[] = ['line' => $line, 'values' => array_combine($headers, $values)];
            }
            return $records ? [$records, null] : [[], 'La hoja Excel solo contiene encabezados; agrega al menos un estudiante.'];
        } finally {
            $zip->close();
        }
    }

    private function normalizeStudentHeaders($headers) {
        $aliases = [
            'first_name' => 'first_name', 'nombres' => 'first_name',
            'last_name' => 'last_name', 'apellidos' => 'last_name',
            'dni' => 'dni', 'edad' => 'age', 'age' => 'age', 'sexo' => 'sex', 'sex' => 'sex',
            'correo' => 'email', 'email' => 'email',
            'especialidad' => 'department_id', 'department_id' => 'department_id',
            'ciclo' => 'cycle_id', 'cycle_id' => 'cycle_id',
            'semestre' => 'semester_id', 'semester_id' => 'semester_id',
            'turno' => 'shift', 'shift' => 'shift'
        ];
        $normalized = [];
        foreach ($headers as $header) {
            $key = mb_strtolower(trim((string) $header), 'UTF-8');
            $normalized[] = $aliases[$key] ?? '';
        }
        $required = ['first_name', 'last_name', 'dni', 'age', 'sex', 'email', 'department_id', 'cycle_id', 'semester_id', 'shift'];
        return count($normalized) === count(array_unique($normalized)) && !in_array('', $normalized, true) && !array_diff($required, $normalized) ? $normalized : null;
    }

    private function validateStudentImport($records, $departments, $cycles, $semesters) {
        $departmentIds = array_fill_keys(array_map(static function ($department) {
            return (string) $department['id'];
        }, $departments), true);
        $cycleIds = array_fill_keys(array_map(static function ($cycle) {
            return (string) $cycle['id'];
        }, $cycles), true);
        $semesterIds = array_fill_keys(array_map(static function ($semester) {
            return (string) $semester['id'];
        }, $semesters), true);
        $departmentNames = [];
        foreach ($departments as $department) $departmentNames[mb_strtolower(trim($department['name']), 'UTF-8')] = (int) $department['id'];
        $cycleNames = [];
        foreach ($cycles as $cycle) $cycleNames[mb_strtolower(trim($cycle['name']), 'UTF-8')] = (int) $cycle['id'];
        $semesterNames = [];
        foreach ($semesters as $semester) $semesterNames[mb_strtolower(trim($semester['name']), 'UTF-8')] = (int) $semester['id'];
        $seenDnis = [];
        $validRows = [];
        $invalidRows = [];

        foreach ($records as $record) {
            $row = array_map(static function ($value) {
                return trim((string) $value);
            }, $record['values']);
            $errors = [];
            if ($row['first_name'] === '' || $row['last_name'] === '') $errors[] = 'Nombres y apellidos son obligatorios.';
            if (!preg_match('/^[0-9]{8}$/', $row['dni'])) $errors[] = 'El DNI debe tener 8 dígitos.';
            if (isset($seenDnis[$row['dni']])) $errors[] = 'El DNI está repetido en este archivo.';
            if ($row['dni'] !== '' && $this->employeeModel->existsByDni($row['dni'])) $errors[] = 'El DNI ya está registrado.';
            $row['age'] = filter_var($row['age'], FILTER_VALIDATE_INT);
            if ($row['age'] === false || $row['age'] < 3 || $row['age'] > 120) $errors[] = 'La edad debe ser un número entre 3 y 120.';
            $row['sex'] = mb_strtolower($row['sex'], 'UTF-8');
            if (!in_array($row['sex'], ['masculino', 'femenino', 'otro'], true)) $errors[] = 'Sexo debe ser masculino, femenino u otro.';
            if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'El correo electrónico no es válido.';
            foreach (['department_id' => [$departmentIds, $departmentNames, 'La especialidad'], 'cycle_id' => [$cycleIds, $cycleNames, 'El ciclo'], 'semester_id' => [$semesterIds, $semesterNames, 'El semestre']] as $field => [$validIds, $validNames, $label]) {
                $value = mb_strtolower($row[$field], 'UTF-8');
                if (ctype_digit($row[$field]) && isset($validIds[$row[$field]])) $row[$field] = (int) $row[$field];
                elseif (isset($validNames[$value])) $row[$field] = $validNames[$value];
                else $errors[] = $label . ' no es válida. Selecciónala desde la lista de Excel.';
            }
            $row['shift'] = mb_strtolower($row['shift'], 'UTF-8');
            if ($row['shift'] === 'mañana') $row['shift'] = 'manana';
            if (!in_array($row['shift'], ['manana', 'tarde'], true)) $errors[] = 'Turno debe ser manana o tarde.';
            if ($row['dni'] !== '') $seenDnis[$row['dni']] = true;

            if ($errors) {
                $invalidRows[] = ['line' => $record['line'], 'dni' => $row['dni'], 'errors' => $errors];
            } else {
                $validRows[] = $row;
            }
        }

        return ['valid_rows' => $validRows, 'invalid_rows' => $invalidRows, 'total' => count($records)];
    }

    private function createStudentXlsxTemplate($path, $departments, $cycles, $semesters) {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return false;
        $escape = static fn($value) => htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $headers = ['Nombres', 'Apellidos', 'DNI', 'Edad', 'Sexo', 'Correo', 'Especialidad', 'Ciclo', 'Semestre', 'Turno'];
        $widths = [22, 26, 14, 10, 15, 30, 42, 16, 16, 14];
        $columns = '';
        foreach ($widths as $index => $width) $columns .= '<col min="' . ($index + 1) . '" max="' . ($index + 1) . '" width="' . $width . '" customWidth="1"/>';
        $row = '<row r="1" ht="26" customHeight="1">';
        foreach ($headers as $index => $header) $row .= '<c r="' . chr(65 + $index) . '1" t="inlineStr" s="1"><is><t>' . $escape($header) . '</t></is></c>';
        $row .= '</row>';
        for ($number = 2; $number <= 1001; $number++) {
            $row .= '<row r="' . $number . '">';
            foreach (range('A', 'J') as $column) $row .= '<c r="' . $column . $number . '" s="2"/>';
            $row .= '</row>';
        }
        $validations = '<dataValidations count="5">'
            . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="E2:E1001"><formula1>Listas!$A$2:$A$4</formula1></dataValidation>'
            . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="G2:G1001"><formula1>Listas!$B$2:$B$' . (count($departments) + 1) . '</formula1></dataValidation>'
            . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="H2:H1001"><formula1>Listas!$C$2:$C$' . (count($cycles) + 1) . '</formula1></dataValidation>'
            . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="I2:I1001"><formula1>Listas!$D$2:$D$' . (count($semesters) + 1) . '</formula1></dataValidation>'
            . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="J2:J1001"><formula1>Listas!$E$2:$E$3</formula1></dataValidation>'
            . '</dataValidations>';
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>' . $columns . '</cols><sheetData>' . $row . '</sheetData>' . $validations . '<autoFilter ref="A1:J1001"/></worksheet>';
        $listRows = '<row r="1"><c r="A1" t="inlineStr"><is><t>Sexo</t></is></c><c r="B1" t="inlineStr"><is><t>Especialidad</t></is></c><c r="C1" t="inlineStr"><is><t>Ciclo</t></is></c><c r="D1" t="inlineStr"><is><t>Semestre</t></is></c><c r="E1" t="inlineStr"><is><t>Turno</t></is></c></row>';
        $lists = [['Masculino', 'Femenino', 'Otro'], array_column($departments, 'name'), array_column($cycles, 'name'), array_column($semesters, 'name'), ['Mañana', 'Tarde']];
        $maximum = max(array_map('count', $lists));
        for ($i = 0; $i < $maximum; $i++) {
            $listRows .= '<row r="' . ($i + 2) . '">';
            foreach ($lists as $col => $list) if (isset($list[$i])) $listRows .= '<c r="' . chr(65 + $col) . ($i + 2) . '" t="inlineStr"><is><t>' . $escape($list[$i]) . '</t></is></c>';
            $listRows .= '</row>';
        }
        $listSheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $listRows . '</sheetData></worksheet>';
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Registro de estudiantes" sheetId="1" r:id="rId1"/><sheet name="Listas" sheetId="2" state="hidden" r:id="rId2"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF17365D"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border></borders><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1"/><xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1"/></cellXfs></styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->addFromString('xl/worksheets/sheet2.xml', $listSheet);
        return $zip->close();
    }

    // 3. MOSTRAR FORMULARIO DE EDICIÓN (Página aparte)
    public function edit() {
        if (isset($_GET['id'])) {
            $emp = $this->employeeModel->getById($_GET['id']);
            
            // También necesitamos departamentos aquí para el select
            $stmt = $this->db->query("SELECT id, nombre AS name, estado AS status FROM especialidades WHERE estado = 'activo'");
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($emp) {
                $cycles = $this->db->query("SELECT id, nombre AS name FROM ciclos ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
                $semesters = $this->db->query("SELECT id, nombre AS name FROM semestres ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
                require_once '../app/views/employees/edit.php';
            } else {
                header("Location: ?c=Employee");
            }
        }
    }

    // 4. GUARDAR CAMBIOS DE EDICIÓN
    public function update_data() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validPersonalData($_POST) || !$this->validAcademicAssignment($_POST)) {
                header("Location: ?c=Employee&a=edit&id=" . (int) $_POST['id'] . "&err=datos_personales");
                exit;
            }
            $current = $this->employeeModel->getById($_POST['id']);
            $photoPath = $this->savePhoto($_FILES['photo'] ?? null, $current['photo_path'] ?? 'default.png');
            $data = [
                'id' => $_POST['id'],
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'dni' => trim($_POST['dni']),
                'age' => (int) $_POST['age'],
                'sex' => $_POST['sex'],
                'email' => $_POST['email'],
                'department_id' => $_POST['department_id'],
                'cycle_id' => (int) $_POST['cycle_id'],
                'semester_id' => (int) $_POST['semester_id'],
                'shift' => $_POST['shift'] ?? 'manana',
                'photo_path' => $photoPath
            ];
            if ($this->employeeModel->update($data)) {
                header("Location: ?c=Employee&msg=actualizado");
            } else {
                header("Location: ?c=Employee&a=edit&id=" . (int) $_POST['id'] . "&err=duplicado");
            }
        }
    }

    // 5. ACTIVAR / DESACTIVAR
    public function toggle() {
        if (isset($_GET['id']) && isset($_GET['status'])) {
            $id = $_GET['id'];
            $currentStatus = $_GET['status'];
            
            // Invertir estado
            $newStatus = ($currentStatus == 'activo') ? 'inactivo' : 'activo';
            
            $this->employeeModel->toggleStatus($id, $newStatus);
            header("Location: ?c=Employee&msg=estado_cambiado");
        }
    }

    // 6. ELIMINAR (Opcional, ya que usamos desactivar)
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?c=Employee&err=metodo_invalido");
            exit;
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            header("Location: ?c=Employee&err=id_invalido");
            exit;
        }

        $deleted = $this->employeeModel->delete($id);
        header("Location: ?c=Employee&" . ($deleted ? "msg=eliminado" : "err=no_eliminado"));
        exit;
    }

    // EXPORTAR LOS ESTUDIANTES MOSTRADOS EN LA TABLA
    public function export() {
        $source = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
        $filters = [
            'search' => trim($source['search'] ?? ''),
            'cycle_id' => trim($source['cycle_id'] ?? ''),
            'semester_id' => trim($source['semester_id'] ?? ''),
            'shift' => trim($source['shift'] ?? ''),
            'department_id' => trim($source['department_id'] ?? '')
        ];
        $selectedIds = $source['ids'] ?? [];
        $employees = $selectedIds
            ? $this->employeeModel->getByIds($selectedIds)
            : $this->employeeModel->read($filters);
        $generatedAt = date('d/m/Y H:i:s');

        $filename = 'Estudiantes_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');

        $escape = static function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

        echo "\xEF\xBB\xBF";
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
            body{font-family:Calibri,Arial,sans-serif;color:#243447}
            .report{width:100%;border-collapse:collapse}
            .title{background:#12304a;color:#fff;font-size:20pt;font-weight:bold;padding:18px}
            .subtitle{background:#eaf2f8;color:#526579;padding:8px 12px;font-size:10pt}
            .metric-label{background:#eaf2f8;color:#526579;font-weight:bold;padding:8px 12px}
            .metric-value{background:#f7fafc;color:#12304a;font-weight:bold;padding:8px 12px}
            .spacer td{height:12px;border:0}
            .headers th{background:#176b87;color:#fff;font-weight:bold;padding:9px 8px;border:1px solid #12566d}
            .data td{padding:7px 8px;border:1px solid #d8e1e8}
            .alt td{background:#f2f7fa}
            .center{text-align:center}
            .text{mso-number-format:"\\@"}
            .active{color:#18794e;font-weight:bold}
            .inactive{color:#7a4f01;font-weight:bold}
        </style></head><body><table class="report">
        <tr><td class="title" colspan="12">REPORTE DE ESTUDIANTES</td></tr>
        <tr><td class="subtitle" colspan="12">IES José Santos Chocano · Control de acceso estudiantil</td></tr>
        <tr><td class="metric-label" colspan="2">Generado el</td><td class="metric-value" colspan="3">' . $escape($generatedAt) . '</td><td class="metric-label" colspan="2">Registros exportados</td><td class="metric-value center" colspan="2">' . count($employees) . '</td><td colspan="3"></td></tr>
        <tr class="spacer"><td colspan="12"></td></tr>
        <tr class="headers"><th>Código</th><th>DNI</th><th>Nombres</th><th>Apellidos</th><th>Edad</th><th>Sexo</th><th>Correo</th><th>Carrera</th><th>Ciclo</th><th>Semestre</th><th>Turno</th><th>Estado</th></tr>';

        foreach ($employees as $employee) {
            $shift = ($employee['shift'] ?? '') === 'manana' ? 'Mañana' : ucfirst($employee['shift'] ?? '');
            $status = ucfirst($employee['status'] ?? '');
            $rowClass = ((int) $employee['id'] % 2 === 0) ? 'data alt' : 'data';
            $statusClass = ($employee['status'] ?? '') === 'activo' ? 'active' : 'inactive';
            echo '<tr class="' . $rowClass . '">'
                . '<td class="text">' . $escape($employee['employee_code'] ?? '') . '</td>'
                . '<td class="text">' . $escape($employee['dni'] ?? '') . '</td>'
                . '<td>' . $escape($employee['first_name'] ?? '') . '</td>'
                . '<td>' . $escape($employee['last_name'] ?? '') . '</td>'
                . '<td class="center">' . $escape($employee['age'] ?? '') . '</td>'
                . '<td>' . $escape(ucfirst($employee['sex'] ?? '')) . '</td>'
                . '<td>' . $escape($employee['email'] ?? '') . '</td>'
                . '<td>' . $escape($employee['department_name'] ?? '') . '</td>'
                . '<td>' . $escape($employee['cycle_name'] ?? $employee['legacy_cycle'] ?? '') . '</td>'
                . '<td>' . $escape($employee['semester_name'] ?? 'Sin asignar') . '</td>'
                . '<td>' . $escape($shift) . '</td>'
                . '<td class="' . $statusClass . '">' . $escape($status) . '</td>'
                . '</tr>';
        }

        echo '</table></body></html>';
        exit;
    }

    public function cards() {
        $selectedIds = $_POST['ids'] ?? $_GET['ids'] ?? [];
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'cycle_id' => trim($_GET['cycle_id'] ?? ''),
            'semester_id' => trim($_GET['semester_id'] ?? ''),
            'shift' => trim($_GET['shift'] ?? ''),
            'department_id' => trim($_GET['department_id'] ?? '')
        ];
        $employees = $selectedIds
            ? $this->employeeModel->getByIds($selectedIds)
            : $this->employeeModel->read($filters);
        $activeCount = count(array_filter($employees, function ($employee) {
            return $employee['status'] === 'activo';
        }));
        require_once '../app/views/employees/cards.php';
    }

    public function qr() {
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        if ($code === '' || strlen($code) > 100) {
            http_response_code(400);
            exit;
        }
        require_once '../app/libs/QrSvg.php';
        QrSvg::output($code);
    }

    private function savePhoto($file, $current = 'default.png') {
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return $current;
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 3 * 1024 * 1024) return $current;

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mime])) return $current;

        $directory = __DIR__ . '/../../public/uploads/student_photos';
        if (!is_dir($directory)) mkdir($directory, 0755, true);
        $filename = 'student_' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) return $current;
        return 'student_photos/' . $filename;
    }

    private function validPersonalData($data) {
        $age = filter_var($data['age'] ?? null, FILTER_VALIDATE_INT);
        $sex = $data['sex'] ?? '';
        $shift = $data['shift'] ?? '';
        $dni = trim($data['dni'] ?? '');
        return $dni !== '' && strlen($dni) <= 20
            && $age !== false && $age >= 3 && $age <= 120
            && in_array($sex, ['masculino', 'femenino', 'otro'], true)
            && in_array($shift, ['manana', 'tarde'], true);
    }

    private function validAcademicAssignment($data) {
        $cycleId = filter_var($data['cycle_id'] ?? null, FILTER_VALIDATE_INT);
        $semesterId = filter_var($data['semester_id'] ?? null, FILTER_VALIDATE_INT);
        return $cycleId !== false && $cycleId > 0
            && $semesterId !== false && $semesterId > 0
            && $this->employeeModel->hasAcademicAssignment($cycleId, $semesterId);
    }
}
?>
