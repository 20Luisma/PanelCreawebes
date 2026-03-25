<?php
namespace Presentation\Controller;

class PreviewController {

    private const ALLOWED_EXTENSIONS = ['html', 'htm', 'php', 'css', 'js', 'txt', 'json', 'xml', 'svg', 'md'];

    public function handleRequest(array $postData): void {
        $codigoBase64 = $postData['codigo'] ?? '';
        $extension    = strtolower(trim($postData['extension'] ?? ''));

        if (!$codigoBase64 || !$extension) {
            http_response_code(400);
            exit('❌ Código o extensión no recibidos.');
        }

        $codigo = base64_decode($codigoBase64);
        if ($codigo === false) {
            http_response_code(400);
            exit('❌ Error al decodificar el contenido.');
        }

        // Seguridad: no ejecutar PHP, solo mostrarlo
        if ($extension === 'html' || $extension === 'htm') {
            header('Content-Type: text/html; charset=UTF-8');
            echo $codigo;
        } else {
            header('Content-Type: text/plain; charset=UTF-8');
            echo $codigo;
        }
    }
}
