<?php
// Comprimir archivos seleccionados en un ZIP
$root = realpath(__DIR__);
$archivos = json_decode($_POST['archivos_json'] ?? '[]', true);
$destinoRel = trim($_POST['destino'] ?? '');
$nombreZip = basename($_POST['nombre'] ?? 'archivo.zip');

$rutaDestino = $destinoRel === '' ? $root : realpath($root . '/' . $destinoRel);
if (!$rutaDestino || strpos($rutaDestino, $root) !== 0) {
    http_response_code(400);
    exit('❌ Carpeta destino inválida.');
}

$zipRuta = $rutaDestino . '/' . $nombreZip;
$zip = new ZipArchive();
if ($zip->open($zipRuta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('❌ No se pudo crear el archivo ZIP.');
}

foreach ($archivos as $relativo) {
    $abs = realpath($root . '/' . $relativo);
    if (!$abs || strpos($abs, $root) !== 0) continue;

    if (is_file($abs)) {
        $zip->addFile($abs, basename($abs));
    } elseif (is_dir($abs)) {
        $dirIter = new RecursiveDirectoryIterator($abs, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterador = new RecursiveIteratorIterator($dirIter);
        foreach ($iterador as $archivo) {
            $rutaInterna = substr($archivo, strlen($abs) + 1);
            $zip->addFile($archivo, basename($abs) . '/' . $rutaInterna);
        }
    }
}
$zip->close();

echo '✅ ZIP creado con éxito';
