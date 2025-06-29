<?php
session_start();
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    http_response_code(403);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$root = realpath(__DIR__);
$query = strtolower(trim($_GET['q'] ?? ''));

if (!$query) {
    echo json_encode([]);
    exit;
}

function buscarRecursivo($base, $rel = '', $query = '') {
    $resultados = [];
    $carpeta = $rel ? "$base/$rel" : $base;
    foreach (scandir($carpeta) as $item) {
        if ($item === '.' || $item === '..') continue;
        $ruta = "$carpeta/$item";
        $rutaRel = ltrim("$rel/$item", '/');
        if (stripos($item, $query) !== false) {
            $resultados[] = [
                'nombre' => $item,
                'ruta'   => $rutaRel,
                'tipo'   => is_dir($ruta) ? 'carpeta' : 'archivo'
            ];
        }
        if (is_dir($ruta)) {
            $resultados = array_merge($resultados, buscarRecursivo($base, $rutaRel, $query));
        }
    }
    return $resultados;
}

$resultados = buscarRecursivo($root, '', $query);
header('Content-Type: application/json');
echo json_encode($resultados);
