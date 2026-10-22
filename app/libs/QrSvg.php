<?php
class QrSvg {
    public static function output($data) {
        require_once __DIR__ . '/phpqrcode/qrlib.php';
        $matrix = QRcode::text($data, false, QR_ECLEVEL_M);
        $quietZone = 2;
        $size = count($matrix) + ($quietZone * 2);
        $path = [];

        foreach ($matrix as $rowIndex => $row) {
            for ($column = 0, $length = strlen($row); $column < $length; $column++) {
                if ($row[$column] === '1') {
                    $path[] = 'M' . ($column + $quietZone) . ' ' . ($rowIndex + $quietZone) . 'h1v1h-1z';
                }
            }
        }

        header('Content-Type: image/svg+xml; charset=UTF-8');
        header('Cache-Control: private, max-age=86400');
        echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $size . ' ' . $size . '" shape-rendering="crispEdges" role="img" aria-label="Código QR">';
        echo '<rect width="100%" height="100%" fill="#fff"/><path d="' . implode('', $path) . '" fill="#000"/></svg>';
        exit;
    }
}
