<?php
namespace Infrastructure\Service;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class DownloadService {
    private PathSecurityService $security;
    private string $rootDir;

    public function __construct(PathSecurityService $security, string $rootDir) {
        $this->security = $security;
        $this->rootDir = $rootDir;
    }

    public function streamFile(string $rutaAbsoluta): void {
        if (function_exists('ob_get_level')) {
            while (ob_get_level() > 0) { @ob_end_clean(); }
        }
        readfile($rutaAbsoluta);
    }

    public function createTempZipFromDirectory(string $rutaDirectorioAbsoluta, bool $esAdmin, bool $overrideActive, ?int $overrideExpiry): ?string {
        if (!class_exists('ZipArchive')) return null;

        set_time_limit(0);

        $tmpZip = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new ZipArchive();
        
        if ($zip->open($tmpZip, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== TRUE) {
            return null;
        }

        $lenBase = strlen($rutaDirectorioAbsoluta) + 1;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rutaDirectorioAbsoluta, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $puedeTocarRootIndex = $this->security->puedeTocarRootIndex($esAdmin, $overrideActive, $overrideExpiry);

        foreach ($it as $file) {
            $filePath = $file->getRealPath();
            if ($filePath === false || $file->isLink()) continue;

            if (!$this->security->estaDentroDe($filePath, $rutaDirectorioAbsoluta)) continue;

            if (is_file($filePath) && $this->security->esRootIndex($filePath, $this->rootDir) && !$puedeTocarRootIndex) continue;

            $rutaInterna = substr($filePath, $lenBase);
            $rutaInterna = $this->security->normalizaRuta($rutaInterna);
            
            if ($rutaInterna === '' || strpos($rutaInterna, '../') !== false || strpos($rutaInterna, "\0") !== false) {
                continue;
            }

            if (is_dir($filePath)) {
                $zip->addEmptyDir($rutaInterna);
            } else {
                $zip->addFile($filePath, $rutaInterna);
            }
        }

        $zip->close();
        return $tmpZip;
    }

    public function cleanTempZip(string $rutaTempZip): void {
        @unlink($rutaTempZip);
    }
}
