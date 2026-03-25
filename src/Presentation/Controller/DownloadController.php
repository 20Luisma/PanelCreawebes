<?php
namespace Presentation\Controller;

use Application\UseCase\DownloadUseCase;
use Infrastructure\Service\DownloadService;

class DownloadController {
    private DownloadUseCase $downloadUseCase;
    private DownloadService $downloadService;

    public function __construct(DownloadUseCase $downloadUseCase, DownloadService $downloadService) {
        $this->downloadUseCase = $downloadUseCase;
        $this->downloadService = $downloadService;
    }

    public function handleRequest(array $getData, bool $esAdmin, bool $overrideActive, ?int $overrideExpiry): void {
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'self';");

        $archivoRelativo = $getData['archivo'] ?? '';
        
        $result = $this->downloadUseCase->processDownload($archivoRelativo, $esAdmin, $overrideActive, $overrideExpiry);

        if (!$result['ok']) {
            header('Content-Type: text/plain; charset=UTF-8');
            exit($result['error']); // Consistente con legacy
        }

        $nombre = $result['nombre'];
        $ruta   = $result['ruta'];
        $size   = $result['size'];

        // Enviar al navegador
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $size);

        if ($result['tipo'] === 'directorio') {
            header('Content-Type: application/zip');
            $this->downloadService->streamFile($ruta);
            $this->downloadService->cleanTempZip($ruta);
        } else {
            header('Content-Type: application/octet-stream');
            $this->downloadService->streamFile($ruta);
        }
        
        exit;
    }
}
