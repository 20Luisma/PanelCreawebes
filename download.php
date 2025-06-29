<?php
$root = realpath(__DIR__);
$archivoRelativo = $_GET['archivo'] ?? '';
$ruta = realpath($root . '/' . $archivoRelativo);

if (!$ruta || strpos($ruta, $root) !== 0) {
    die("❌ Ruta inválida o no permitida.");
}

// Si es archivo, descargar directamente
if (is_file($ruta)) {
    $nombre = basename($ruta);
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($ruta));
    readfile($ruta);
    exit;
}

// Si es carpeta, crear ZIP y descargar
if (is_dir($ruta)) {
    $nombreZip = basename($ruta) . '.zip';
    $tmpZip = tempnam(sys_get_temp_dir(), 'zip');

    $zip = new ZipArchive();
    $zip->open($tmpZip, ZipArchive::OVERWRITE | ZipArchive::CREATE);

    $lenBase = strlen($ruta) + 1;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($ruta, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
        $rutaInterna = substr($file, $lenBase);
        if ($file->isDir()) {
            $zip->addEmptyDir($rutaInterna);
        } else {
            $zip->addFile($file, $rutaInterna);
        }
    }

    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $nombreZip . '"');
    header('Content-Length: ' . filesize($tmpZip));
    readfile($tmpZip);
    unlink($tmpZip);
    exit;
}

die("❌ No se encontró el recurso.");
