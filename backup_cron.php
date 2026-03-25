<?php
// backup_cron.php – Backup automático por cron (robusto para >10 GB)

if (php_sapi_name() !== 'cli') {
    // Aun así funciona vía web, pero cron/CLI es lo recomendado
}

ignore_user_abort(true);
set_time_limit(0);
@ini_set('memory_limit', '512M');

$root       = realpath(__DIR__);
$ts         = date('Y-m-d_H-i-s');
$dirBackup  = $root . '/_backups/registros';
$dirInforme = $root . '/_backups/informes';
$dirCache   = $root . '/_backups/.cache';
$zipName    = "backup_$ts.zip";
$zipPathTmp = "$dirBackup/$zipName.tmp"; // temporal
$zipPath    = "$dirBackup/$zipName";
$logFile    = "$dirInforme/log_backup_$ts.txt";

$maxLogs    = 14;     // conserva 14 logs
$maxZips    = 7;      // conserva 7 zips
$storeFrom  = 100 * 1024 * 1024; // >100 MB almacena sin comprimir (STORE)
$skipZipExt = true;   // no meter .zip dentro del zip

$excluir = [
    '_backups',
    '.papelera_creawebes',
    'usuarios',
    '.instalador_creawebes',
];

function logl($msg) {
    global $logFile;
    file_put_contents($logFile, "[".date('H:i:s')."] $msg\n", FILE_APPEND);
}

function starts_with($haystack, $needle) {
    return strncmp($haystack, $needle, strlen($needle)) === 0;
}

// Crear carpetas
@mkdir($dirBackup, 0775, true);
if (!is_dir($dirInforme)) {
    @mkdir($dirInforme, 0775, true);
    @file_put_contents($dirInforme . '/.htaccess', "Deny from all");
}
@mkdir($dirCache, 0775, true);

// Iniciar log
file_put_contents($logFile, "=== Backup automático iniciado: $ts ===\n");
logl("Raíz: $root");

// Comprobar ZipArchive
if (!class_exists('ZipArchive')) {
    logl("❌ ZipArchive no disponible");
    exit(1);
}

$zip = new ZipArchive;
if ($zip->open($zipPathTmp, ZipArchive::CREATE) !== TRUE) {
    logl("❌ No se pudo crear ZIP temporal: $zipPathTmp");
    exit(1);
}

// Filtro para no entrar en carpetas excluidas
$dirIter = new RecursiveDirectoryIterator(
    $root,
    FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
);

$filterIter = new RecursiveCallbackFilterIterator(
    $dirIter,
    function ($current, $key, $iterator) use ($root, $excluir) {
        $rel = ltrim(str_replace(['\\', $root], ['/', ''], $current->getPathname()), '/');
        foreach ($excluir as $ex) {
            if (starts_with($rel, $ex . '/')) {
                return false; // no entrar
            }
            if ($rel === $ex) {
                return false;
            }
        }
        return true;
    }
);

$it = new RecursiveIteratorIterator($filterIter, RecursiveIteratorIterator::LEAVES_ONLY);

$total = 0;
$added = 0;
$skips = 0;
$start = microtime(true);

foreach ($it as $f) {
    /** @var SplFileInfo $f */
    if (!$f->isFile()) continue;
    $total++;

    $abs = $f->getRealPath();
    $rel = ltrim(str_replace(['\\', $root . DIRECTORY_SEPARATOR], ['/', ''], $abs), '/');

    // Saltar .zip si está activado
    if ($skipZipExt && preg_match('/\.zip$/i', $rel)) { $skips++; continue; }

    // Saltar no legibles
    if (!is_readable($abs)) { $skips++; logl("Skip (no legible): $rel"); continue; }

    $size = $f->getSize();

    // Elegir compresión: STORE para archivos grandes, DEFLATE para el resto
    $compression = ($size >= $storeFrom) ? ZipArchive::CM_STORE : ZipArchive::CM_DEFLATE;

    // Añadir
    if (!$zip->addFile($abs, $rel)) {
        $skips++; logl("Skip (fallo addFile): $rel");
        continue;
    }

    // Asegurar compresión elegida
    if (!$zip->setCompressionName($rel, $compression)) {
        // si falla, al menos ya está añadido con la compresión por defecto
    }

    $added++;

    // Log esporádico
    if ($added % 1000 === 0) {
        $elapsed = microtime(true) - $start;
        logl("Progreso: $added añadidos / $total vistos (t=".round($elapsed,1)."s)");
    }
}

// Cerrar ZIP y renombrar de forma atómica
if (!$zip->close()) {
    logl("❌ Error al cerrar ZIP (posible falta de ZIP64 en el hosting)");
    // dejamos el .tmp para diagnóstico
    exit(1);
}

if (!@rename($zipPathTmp, $zipPath)) {
    logl("❌ No se pudo renombrar ZIP temporal a definitivo");
    // intentamos copiar como fallback
    if (@copy($zipPathTmp, $zipPath)) {
        @unlink($zipPathTmp);
        logl("⚠️  Hecho fallback por copia");
    } else {
        logl("❌ Falló también la copia de respaldo");
        exit(1);
    }
}

// Rotación de backups
$existentes = glob("$dirBackup/backup_*.zip");
usort($existentes, fn($a, $b) => filemtime($b) - filemtime($a));
foreach (array_slice($existentes, $maxZips) as $f) @unlink($f);

// Rotación de logs
$logs = glob("$dirInforme/log_backup_*.txt");
usort($logs, fn($a, $b) => filemtime($b) - filemtime($a));
foreach (array_slice($logs, $maxLogs) as $lf) @unlink($lf);

// Resumen final
$elapsed = microtime(true) - $start;
$tamMB   = file_exists($zipPath) ? (filesize($zipPath) / 1024 / 1024) : 0;

logl("Añadidos: $added | Saltados: $skips | Vistos: $total");
logl("Tamaño final: ".round($tamMB,2)." MB");
logl("Duración: ".round($elapsed,1)." s");
logl("ZIP: $zipPath");
file_put_contents($logFile, "=== Backup finalizado ===\n", FILE_APPEND);

// Mensaje para cron/mail
echo "✅ Backup OK: $zipName (".round($tamMB,2)." MB) — añadidos:$added, saltados:$skips, duración:".round($elapsed,1)."s\n";
