<?php
// ---------- Helpers ----------
function obtenerRutaRelativa($root, $abs) {
    return ltrim(str_replace($root, '', $abs), '/\\');
}
function iconoArchivo($n) {
    $ext = strtolower(pathinfo($n, PATHINFO_EXTENSION));
    return match ($ext) {
        'jpg','jpeg','png','gif','webp' => '🖼️',
        'pdf'                           => '📄',
        'php','html','js','css','txt'         => '💻', // Agregado 'txt' para el icono de código
        'zip','rar'                     => '🗜️',
        'mp3','wav'                     => '🎵',
        'mp4','mov'                     => '🎞️',
        default                         => '📄',
    };
}
function breadcrumb($rel) {
    if (!$rel) return '📁 <strong>Inicio</strong>';
    $p   = explode('/', $rel);
    $out = ['<a href="index.php">Inicio</a>'];
    $acc = [];
    foreach ($p as $seg) {
        $acc[] = $seg;
        $out[] = '<a href="index.php?carpeta=' . urlencode(implode('/', $acc)) . '">' .
                 htmlspecialchars($seg) . '</a>';
    }
    return '📁 ' . implode(' / ', $out);
}
function duplicarCarpeta($src, $dst) {
    mkdir($dst);
    foreach (scandir($src) as $i) {
        if ($i === '.' || $i === '..') continue;
        $s = "$src/$i";
        $d = "$dst/$i";
        is_dir($s) ? duplicarCarpeta($s, $d) : copy($s, $d);
    }
}
/* Elimina recursivamente archivos o carpetas (para sobrescribir) */
function eliminarRecursivo($ruta) {
    if (is_dir($ruta) && !is_link($ruta)) {
        foreach (scandir($ruta) as $i) {
            if ($i === '.' || $i === '..') continue;
            eliminarRecursivo("$ruta/$i");
        }
        rmdir($ruta);
    } elseif (file_exists($ruta)) {
        unlink($ruta);
    }
}
function listarCarpetas($ruta, $base = '', $root = '') {
    $root = $root ?: $ruta;
    $out  = [];
    foreach (scandir($ruta) as $i) {
        if ($i === '.' || $i === '..') continue;
        $abs = "$ruta/$i";
        if (is_dir($abs)) {
            // Asegurarse de no listar la papelera a menos que estemos dentro de ella
            if ($abs === $root . '/.papelera_creawebes' && $base === '') {
                continue;
            }
            $rel  = ltrim("$base/$i", '/');
            $out[] = $rel;
            $out   = array_merge($out, listarCarpetas($abs, $rel, $root));
        }
    }
    return $out;
}
