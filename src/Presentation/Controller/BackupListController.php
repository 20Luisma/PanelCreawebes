<?php
namespace Presentation\Controller;

use Infrastructure\Service\PathSecurityService;

class BackupListController {
    private PathSecurityService $security;
    private string $rootDir;

    public function __construct(PathSecurityService $security, string $rootDir) {
        $this->security = $security;
        $this->rootDir = $rootDir;
    }

    public function handleRequest(array $getData): void {
        $carpeta = $getData['carpeta'] ?? '';
        $archivo = $getData['archivo'] ?? '';

        $dirBacks = $this->rootDir . '/respaldo/' . $carpeta . $archivo;
        $dirBacksReal = realpath($dirBacks);

        if (!$dirBacksReal || !is_dir($dirBacksReal) || !$this->security->estaDentroDe($dirBacksReal, $this->rootDir)) {
            die('❌ No hay respaldos disponibles para este archivo.');
        }

        $archivos = scandir($dirBacksReal);
        usort($archivos, fn($a, $b) => strcmp($b, $a));

        echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'>";
        echo "<title>Respaldos de " . htmlspecialchars($archivo) . "</title>";
        echo "<style>
            body{font-family:sans-serif;background:#f9f9f9;padding:2rem}
            h2{color:#3949ab}
            a{display:block;margin:.5rem 0;color:#1565c0;text-decoration:none}
            a:hover{text-decoration:underline}
        </style></head><body>";
        echo "<h2>🕘 Respaldos de <code>" . htmlspecialchars($archivo) . "</code></h2>";

        foreach ($archivos as $nombre) {
            if ($nombre === '.' || $nombre === '..') continue;
            $rutaRel = "respaldo/" . htmlspecialchars($carpeta . $archivo . "/" . $nombre);
            echo "<a href='" . $rutaRel . "' download>📥 " . htmlspecialchars($nombre) . "</a>";
        }

        echo "</body></html>";
    }
}
