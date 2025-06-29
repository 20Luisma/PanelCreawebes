<?php
// Descomprimir ZIP a una carpeta seleccionada
$root = realpath(__DIR__);
$archivoZip = $_POST['archivo'] ?? '';
$destinoRel = trim($_POST['destino'] ?? '');

$rutaZip = realpath($root . '/' . $archivoZip);
$rutaDestino = $destinoRel === '' ? $root : realpath($root . '/' . $destinoRel);

if (!$rutaZip || !is_file($rutaZip) || pathinfo($rutaZip, PATHINFO_EXTENSION) !== 'zip') {
    http_response_code(400);
    exit('❌ Archivo ZIP inválido.');
}

if (!$rutaDestino || strpos($rutaDestino, $root) !== 0) {
    http_response_code(400);
    exit('❌ Carpeta destino inválida.');
}

$zip = new ZipArchive();
if ($zip->open($rutaZip) !== true) {
    http_response_code(500);
    exit('❌ No se pudo abrir el ZIP.');
}

$zip->extractTo($rutaDestino);
$zip->close();

echo '✅ ZIP descomprimido con éxito';
