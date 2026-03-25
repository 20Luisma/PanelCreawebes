<?php
namespace Application\UseCase;

class RestoreListUseCase {
    private string $backupDir;

    public function __construct(string $backupDir) {
        $this->backupDir = $backupDir;
    }

    /**
     * Devuelve la lista de ZIPs disponibles para restaurar,
     * ordenados del más reciente al más antiguo.
     */
    public function getAvailableBackups(): array {
        if (!is_dir($this->backupDir)) {
            return [];
        }

        $zips = glob($this->backupDir . '/*.zip');
        if (!$zips) return [];

        usort($zips, fn($a, $b) => (@filemtime($b) ?: 0) - (@filemtime($a) ?: 0));

        return array_map(function($z) {
            return [
                'path'     => $z,
                'basename' => basename($z),
                'date'     => date('d-m-Y H:i', @filemtime($z) ?: 0),
                'size_mb'  => number_format((@filesize($z) ?: 0) / 1048576, 2),
            ];
        }, $zips);
    }
}
