<?php
namespace Application\UseCase;

use Infrastructure\Service\ZipService;

class ZipUseCase {
    private ZipService $zipService;
    private string $rootDir;

    public function __construct(ZipService $zipService, string $rootDir) {
        $this->zipService = $zipService;
        $this->rootDir = $rootDir;
    }

    public function compress(array $archivosRelativos, string $destinoRelativo, string $nombreZipDeseado): array {
        if ($destinoRelativo === 'root' || $destinoRelativo === '') {
            $rutaDestinoAbs = $this->rootDir;
            $nombreCarpetaDestino = 'Inicio (Raíz)';
        } else {
            $rutaDestinoAbs = realpath($this->rootDir . '/' . $destinoRelativo);
            $nombreCarpetaDestino = "'" . htmlspecialchars($destinoRelativo) . "'";
            
            if (!$rutaDestinoAbs || strpos($rutaDestinoAbs, $this->rootDir) !== 0) {
                return ['ok' => false, 'error' => "❌ Error: Ruta de destino inválida o fuera del directorio permitido."];
            }
        }

        $result = $this->zipService->compressFiles($archivosRelativos, $rutaDestinoAbs, $nombreZipDeseado);

        if (!$result['ok']) {
            return ['ok' => false, 'error' => "❌ " . $result['error']];
        }

        return [
            'ok' => true,
            'html' => "✅ ZIP creado exitosamente como <strong>" . htmlspecialchars($result['nombre_final']) . "</strong> en la carpeta <strong>" . $nombreCarpetaDestino . "</strong>."
        ];
    }

    public function extract(string $archivoZipRel, string $destinoRel, bool $esAdmin, bool $overrideActive, ?int $overrideExpiry): array {
        $rutaZipAbs = realpath($this->rootDir . '/' . $archivoZipRel);
        $rutaDestinoAbs = rtrim($this->rootDir . '/' . $destinoRel, '/');

        $result = $this->zipService->extractZip($rutaZipAbs, $rutaDestinoAbs, $esAdmin, $overrideActive, $overrideExpiry);

        if (!$result['ok']) {
            return ['ok' => false, 'error' => "❌ " . $result['error']];
        }

        return ['ok' => true, 'mensajes' => $result['mensajes']];
    }
}
