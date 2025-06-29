<?php
// backup_cron.php – Copia de seguridad automática (web + cron seguro)

$root  = realpath(__DIR__);
$esCli = php_sapi_name() === 'cli';

if (!$esCli) {
    $token = $_GET['token'] ?? '';
    if ($token !== 'mi_token_alfred_2025') {
        http_response_code(403);
        exit('❌ Token incorrecto o ausente.');
    }
}

// --- RUTAS ---
$backupDir   = $root . '/_backups/registros';
$informesDir = $root . '/_backups/informes';
$tanda       = 40;

// Crear carpetas si no existen
if (!is_dir($backupDir)) mkdir($backupDir, 0775, true);
if (!is_dir($informesDir)) {
    mkdir($informesDir, 0775, true);
    file_put_contents($informesDir . '/.htaccess', "Deny from all");
}

// Eliminar backups antiguos (mantener 7)
$backups = glob($backupDir . '/backup_*.zip');
usort($backups, fn($a, $b) => filemtime($a) - filemtime($b));
while (count($backups) >= 7) unlink(array_shift($backups));

// --- CREAR ZIP ---
$fechaHora = date('Y-m-d_H-i-s');
$zipName   = "backup_$fechaHora.zip";
$zipPath   = "$backupDir/$zipName";

$zip = new ZipArchive;
if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
    exit('❌ No se pudo crear ZIP.');
}

$excluir = ['/_backups', '/.git', '/node_modules', '/vendor'];
$tamañoMaximo = 500 * 1024 * 1024; // 500 MB

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($it as $f) {
    $rutaAbs = $f->getRealPath();
    $rutaRel = ltrim(str_replace($root, '', $rutaAbs), '/\\');

    // Excluir carpetas específicas
    foreach ($excluir as $rutaExcluida) {
        if (strpos($rutaAbs, $rutaExcluida) !== false) continue 2;
    }

    // Excluir archivos muy grandes
    if ($f->getSize() > $tamañoMaximo) continue;

    // Excluir archivos ocultos
    if (basename($rutaAbs)[0] === '.') continue;

    $zip->addFile($rutaAbs, $rutaRel);
}

// Intentar cerrar el ZIP correctamente
if ($zip->close()) {
    clearstatcache();
    if (file_exists($zipPath)) {
        echo "✅ Backup creado correctamente: " . basename($zipPath) . " (" . round(filesize($zipPath)/1024/1024, 2) . " MB)";
    } else {
        echo "❌ ZIP cerrado pero no se encuentra el archivo.";
    }
} else {
    unlink($zipPath); // Eliminar si está corrupto
    echo "❌ No se pudo cerrar correctamente el ZIP. Eliminado.";
}
