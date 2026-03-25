<?php
namespace Application\UseCase;

use Infrastructure\Service\DownloadService;
use Infrastructure\Service\PathSecurityService;

class DownloadUseCase {
    private DownloadService $downloadService;
    private PathSecurityService $security;
    private string $rootDir;

    public function __construct(DownloadService $downloadService, PathSecurityService $security, string $rootDir) {
        $this->downloadService = $downloadService;
        $this->security = $security;
        $this->rootDir = $rootDir;
    }

    public function processDownload(string $archivoRelativo, bool $esAdmin, bool $overrideActive, ?int $overrideExpiry): array {
        $rutaResuelta = realpath($this->rootDir . '/' . $archivoRelativo);

        if (!$rutaResuelta || !file_exists($rutaResuelta) || !$this->security->estaDentroDe($rutaResuelta, $this->rootDir)) {
            return ['ok' => false, 'error' => "❌ Ruta inválida o no permitida."];
        }

        if (is_file($rutaResuelta) && $this->security->esRootIndex($rutaResuelta, $this->rootDir) && !$this->security->puedeTocarRootIndex($esAdmin, $overrideActive, $overrideExpiry)) {
            return ['ok' => false, 'error' => "🚫 La descarga de root/index.php está protegida."];
        }

        if (is_file($rutaResuelta)) {
            return [
                'ok' => true, 
                'tipo' => 'archivo', 
                'ruta' => $rutaResuelta, 
                'nombre' => basename($rutaResuelta),
                'size' => filesize($rutaResuelta)
            ];
        }

        if (is_dir($rutaResuelta)) {
            $tmpZip = $this->downloadService->createTempZipFromDirectory($rutaResuelta, $esAdmin, $overrideActive, $overrideExpiry);
            
            if (!$tmpZip) {
                return ['ok' => false, 'error' => "❌ Extensión ZIP no disponible o no se pudo crear el archivo ZIP temporal."];
            }

            return [
                'ok' => true,
                'tipo' => 'directorio',
                'ruta' => $tmpZip,
                'nombre' => basename($rutaResuelta) . '.zip',
                'size' => filesize($tmpZip)
            ];
        }

        return ['ok' => false, 'error' => "❌ No se encontró el recurso o no es descargable."];
    }
}
