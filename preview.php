<?php
// Vista previa sin guardar – Seguro y temporal

$codigoBase64 = $_POST['codigo'] ?? '';
$extension    = strtolower(trim($_POST['extension'] ?? ''));

// Validación básica
if (!$codigoBase64 || !$extension) {
    http_response_code(400);
    exit('❌ Código o extensión no recibidos.');
}

// Decodificar el contenido
$codigo = base64_decode($codigoBase64);
if ($codigo === false) {
    http_response_code(400);
    exit('❌ Error al decodificar el contenido.');
}

// Mostrar como página si es HTML, como texto si es PHP o cualquier otra
if ($extension === 'html' || $extension === 'htm') {
    header('Content-Type: text/html; charset=UTF-8');
    echo $codigo;
    exit;
}

header('Content-Type: text/plain; charset=UTF-8');
echo "📄 Vista previa de archivo .{$extension}:\n\n";
echo $codigo;
