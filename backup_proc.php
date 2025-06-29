<?php
session_start();
header('Content-Type: application/json');

// Seguridad: solo usuarios logueados
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Ajustes
$root       = realpath(__DIR__);
$panel      = $root . '/panel';
$archivos   = [];
$accion     = $_POST['accion'] ?? '';
$timestamp  = date('Y-m-d_H-i-s');
$nombreZip  = "backup_$timestamp.zip";
$dirBackup  = $root . '/_backups/registros';
$dirInforme = $root . '/_backups/informes';

// Crear carpetas si no existen
if (!is_dir($dirBackup)) mkdir($dirBackup, 0775, true);
if (!is_dir($dirInforme)) {
    mkdir($dirInforme, 0775, true);
    file_put_contents($dirInforme . '/.htaccess', "Deny from all");
}

// Cache temporal de archivos
$cacheFile = $root . '/_cache_archivos_backup.json';

if ($accion === 'init') {
    // Buscar archivos
    $archivos = buscarArchivos($panel);
    $archivos[] = $root . '/index.php'; // Incluir index raíz
    file_put_contents($cacheFile, json_encode($archivos));
    echo json_encode(['total' => count($archivos)]);
    exit;
}

if ($accion === 'chunk') {
    if (!file_exists($cacheFile)) {
        echo json_encode(['error' => 'Cache no encontrada']);
        exit;
    }

    $archivos = json_decode(file_get_contents($cacheFile), true);
    $zipPath  = "$dirBackup/$nombreZip";
    $zip      = new ZipArchive;

    if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
        echo json_encode(['error' => 'No se pudo crear ZIP']);
        exit;
    }

    $procesados = 0;
    foreach ($archivos as $archivo) {
        if (!file_exists($archivo)) continue;
        $rutaInterna = str_replace($root . '/', '', $archivo);
        $zip->addFile($archivo, $rutaInterna);
        $procesados++;
    }

    $zip->close();
    unlink($cacheFile); // Borrar cache

    // Crear informe
    $informe = "$dirInforme/informe_$timestamp.txt";
    $contenido = "Backup creado el $timestamp\nArchivos incluidos:\n";
    $contenido .= implode("\n", array_map(fn($a) => str_replace($root . '/', '', $a), $archivos));
    file_put_contents($informe, $contenido);

    // Limitar a los 7 más recientes
    $existentes = glob("$dirBackup/backup_*.zip");
    usort($existentes, fn($a, $b) => filemtime($b) - filemtime($a));
    foreach (array_slice($existentes, 7) as $f) unlink($f);

    echo json_encode([
        'finalizado' => true,
        'nombre'     => $nombreZip,
        'informe'    => basename($informe),
        'procesados' => $procesados
    ]);
    exit;
}

// ---------------- FUNCIONES ----------------

function buscarArchivos($base) {
    $archivos = [];
    $items = scandir($base);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $ruta = $base . '/' . $item;
        if (is_dir($ruta)) {
            $archivos = array_merge($archivos, buscarArchivos($ruta));
        } else {
            $archivos[] = $ruta;
        }
    }
    return $archivos;
}
