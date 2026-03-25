<?php
namespace Presentation\Controller;

use Application\UseCase\ZipUseCase;

class ZipController {
    private ZipUseCase $zipUseCase;

    public function __construct(ZipUseCase $zipUseCase) {
        $this->zipUseCase = $zipUseCase;
    }

    public function handleCompressRequest(array $postData): void {
        $destinoRel = $postData['destino'] ?? 'root';
        $archivos = json_decode($postData['archivos_json'] ?? '[]', true);
        $nombreZipDeseado = $postData['nombre_zip'] ?? 'backup_' . date('Y-m-d_H-i-s') . '.zip';

        if (!is_array($archivos)) {
            $archivos = [];
        }

        $result = $this->zipUseCase->compress($archivos, $destinoRel, $nombreZipDeseado);

        if (!$result['ok']) {
            die($result['error']); // Mantiene el comportamiento original de die() con texto que espera la UI
        }

        echo $result['html'];
    }

    public function handleExtractRequest(array $postData, bool $esAdmin, bool $overrideActive, ?int $overrideExpiry): void {
        // Cabeceras de seguridad que ya estaban en descomprimir.php
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; base-uri 'self'; form-action 'self'; frame-ancestors 'self';");
        header('Content-Type: text/plain; charset=UTF-8');

        $archivoZipRel = $postData['archivo'] ?? '';
        $destinoRel    = $postData['destino'] ?? '';

        $result = $this->zipUseCase->extract($archivoZipRel, $destinoRel, $esAdmin, $overrideActive, $overrideExpiry);

        if (!$result['ok']) {
            die($result['error']);
        }

        echo implode("\n", $result['mensajes']);
    }
}
