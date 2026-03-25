<?php
namespace Presentation\Controller;

use Infrastructure\Service\PathSecurityService;

class FolderTreeController {
    private PathSecurityService $security;
    private string $rootDir;

    private const HIDDEN_DIRS = ['src', 'vendor', 'node_modules', '.git', '.papelera_creawebes', 'memory_backups',
        '_backups', 'Historiales', '_informes_restore', '.instalador_creawebes'];

    public function __construct(PathSecurityService $security, string $rootDir) {
        $this->security = $security;
        $this->rootDir = $rootDir;
    }

    public function handleRequest(): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->scanFolders($this->rootDir, $this->rootDir));
    }

    private function scanFolders(string $base, string $root, int $nivel = 0, int $maxNivel = 4): array {
        if ($nivel >= $maxNivel) return [];
        $lista = [];
        $items = @scandir($base);
        if (!$items) return [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item[0] === '.') continue;
            if (in_array($item, self::HIDDEN_DIRS, true)) continue;

            $abs = $base . '/' . $item;
            if (!is_dir($abs)) continue;
            if (!$this->security->estaDentroDe(realpath($abs), $root)) continue;

            $rel = ltrim(str_replace($root, '', realpath($abs)), '/\\');
            $hijos = $this->scanFolders($abs, $root, $nivel + 1, $maxNivel);

            $lista[] = [
                'nombre' => $item,
                'ruta'   => $rel,
                'hijos'  => $hijos,
            ];
        }
        return $lista;
    }
}
