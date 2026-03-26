<?php
/**
 * API endpoint liviano para listar carpetas.
 * Devuelve JSON con el árbol de carpetas (hasta 4 niveles).
 * Se consume vía AJAX para no bloquear el render de la página.
 */
require_once __DIR__ . '/verificar_sesion.php';
session_write_close(); // Libera el lock de la sesión para evitar blocking de concurrencia //

header('Content-Type: application/json; charset=utf-8');

$root = realpath(__DIR__);

function escanearCarpetas(string $base, string $root, int $nivel = 0, int $maxNivel = 4): array {
    if ($nivel >= $maxNivel) return [];
    $lista = [];
    $ocultas = ['src', 'vendor', 'node_modules', '.git', '.papelera_creawebes', 'memory_backups'];
    $items = @scandir($base);
    if (!$items) return [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item[0] === '.') continue;
        if (in_array($item, $ocultas, true)) continue;
        $ruta = $base . '/' . $item;
        if (is_dir($ruta)) {
            $rel = ltrim(str_replace($root, '', $ruta), '/\\');
            $indent = str_repeat('— ', $nivel);
            $lista[] = ['ruta' => $rel, 'nombre' => $indent . ($rel ?: '/')];
            $lista = array_merge($lista, escanearCarpetas($ruta, $root, $nivel + 1, $maxNivel));
        }
    }
    return $lista;
}

echo json_encode(escanearCarpetas($root, $root), JSON_UNESCAPED_UNICODE);
