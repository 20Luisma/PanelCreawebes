<?php
namespace Infrastructure\Service;

use Exception;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use SplFileObject;

class BackupService {
    private string $root;
    private string $dirBackup;
    private string $dirInforme;
    private string $dirCache;
    private string $stateFile;
    private string $lockFile;

    public function __construct(string $root) {
        $this->root       = realpath($root);
        $this->dirBackup  = $this->root . '/_backups/registros';
        $this->dirInforme = $this->root . '/_backups/informes';
        $this->dirCache   = $this->root . '/_backups/.cache';

        if (!is_dir($this->dirBackup))  @mkdir($this->dirBackup, 0775, true);
        if (!is_dir($this->dirInforme)) {
            @mkdir($this->dirInforme, 0775, true);
            @file_put_contents($this->dirInforme . '/.htaccess', "Deny from all");
        }
        if (!is_dir($this->dirCache)) {
            @mkdir($this->dirCache, 0775, true);
            @file_put_contents($this->dirCache . '/.htaccess', "Deny from all");
        }

        $this->stateFile = $this->dirCache . '/estado_backup.json';
        $this->lockFile  = $this->dirCache . '/lock_backup.lck';
    }

    public function init(): array {
        $lockFp = fopen($this->lockFile, 'c+');
        if ($lockFp) flock($lockFp, LOCK_EX);

        @unlink($this->stateFile);
        $ts       = date('Y-m-d_H-i-s');
        $zipPath  = $this->dirBackup . "/backup_$ts.zip";
        $manifest = $this->dirCache  . "/manifest_$ts.txt";

        try {
            $total = 0;
            $this->createManifest($manifest, $total);

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                throw new Exception('No se pudo crear el ZIP inicial');
            }
            $zip->close();

            $state = [
                'timestamp' => $ts,
                'zip'       => $zipPath,
                'manifest'  => $manifest,
                'index'     => 0,
                'total'     => $total,
                'done'      => 0,
            ];
            $this->saveState($state);

            if ($lockFp) flock($lockFp, LOCK_UN);
            return ['total' => $total, 'zip' => basename($zipPath), 'timestamp' => $ts];
        } catch (Exception $e) {
            if ($lockFp) flock($lockFp, LOCK_UN);
            return ['error' => 'INIT: ' . $e->getMessage()];
        }
    }

    public function status(): array {
        $state = $this->loadState();
        if (!$state) return ['error' => 'No hay estado activo'];
        $total = max(1, (int)$state['total']);
        $done  = (int)$state['done'];
        return [
            'total'      => (int)$state['total'],
            'done'       => $done,
            'restantes'  => max(0, (int)$state['total'] - $done),
            'porcentaje' => round(($done / $total) * 100, 2),
            'zip'        => basename($state['zip']),
            'timestamp'  => $state['timestamp'],
        ];
    }

    public function abort(): array {
        $state = $this->loadState();
        if ($state && !empty($state['manifest']) && file_exists($state['manifest'])) {
            @unlink($state['manifest']);
        }
        @unlink($this->stateFile);
        return ['abortado' => true];
    }

    public function chunk(int $batch = 300): array {
        $lockFp = fopen($this->lockFile, 'c+');
        if ($lockFp) flock($lockFp, LOCK_EX);

        $state = $this->loadState();
        if (!$state) {
            if ($lockFp) flock($lockFp, LOCK_UN);
            return ['error' => 'Estado no encontrado. Ejecuta accion=init primero.'];
        }

        $zipPath  = $state['zip'];
        $manifest = $state['manifest'];
        $index    = (int)$state['index'];
        $total    = (int)$state['total'];
        $done     = (int)$state['done'];

        if (!file_exists($manifest)) {
            if ($lockFp) flock($lockFp, LOCK_UN);
            return ['error' => 'Manifiesto no encontrado. Aborta e inicia de nuevo.'];
        }

        if ($index >= $total) {
            if ($lockFp) flock($lockFp, LOCK_UN);
            return ['finalizado' => true, 'nombre' => basename($zipPath), 'procesados' => $done, 'restantes' => 0, 'porcentaje' => 100];
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            if ($lockFp) flock($lockFp, LOCK_UN);
            return ['error' => 'No se pudo abrir/crear el ZIP para escribir.'];
        }

        $sfo = new SplFileObject($manifest, 'r');
        $sfo->setFlags(SplFileObject::DROP_NEW_LINE);
        $sfo->seek($index);

        $procesados_ahora = 0;
        for ($i = 0; $i < $batch && !$sfo->eof(); $i++) {
            $abs = trim((string)$sfo->current());
            $sfo->next();
            if ($abs === '') { $index++; continue; }
            if (!file_exists($abs)) { $index++; continue; }

            $rel = $this->normalizeRel($abs);
            if ($this->shouldExclude($rel)) { $index++; continue; }

            if ($zip->addFile($abs, $rel)) {
                $procesados_ahora++;
            }
            $index++;
        }

        $zip->close();

        $done += $procesados_ahora;
        $state['index'] = $index;
        $state['done']  = $done;
        $this->saveState($state);

        $restantes  = max(0, $total - $done);
        $porcentaje = round(($done / max(1, $total)) * 100, 2);

        if ($index >= $total) {
            @unlink($manifest);
            @unlink($this->stateFile);

            $informe = $this->dirInforme . "/informe_{$state['timestamp']}.txt";
            $contenido = "Backup manual (GRANDE) creado el {$state['timestamp']}\n"
                       . "ZIP: " . basename($zipPath) . "\n"
                       . "Total archivos: $total\n";
            @file_put_contents($informe, $contenido);
            $this->rotateBackups(7);

            if ($lockFp) flock($lockFp, LOCK_UN);
            return [
                'finalizado' => true,
                'nombre'     => basename($zipPath),
                'informe'    => basename($informe),
                'procesados' => $done,
                'restantes'  => 0,
                'porcentaje' => 100,
            ];
        }

        if ($lockFp) flock($lockFp, LOCK_UN);
        return [
            'finalizado' => false,
            'procesados' => $done,
            'restantes'  => $restantes,
            'porcentaje' => $porcentaje,
        ];
    }

    // --- Helpers privados ---

    private function createManifest(string $manifestPath, int &$total): void {
        $total = 0;
        $fh = fopen($manifestPath, 'w');
        if (!$fh) throw new Exception('No se pudo crear manifiesto');

        $rii = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($rii as $file) {
            if ($file->isDir()) continue;
            $abs = $file->getPathname();
            $rel = $this->normalizeRel($abs);
            if ($this->shouldExclude($rel)) continue;
            fwrite($fh, $abs . "\n");
            $total++;
        }
        fclose($fh);
    }

    private function normalizeRel(string $abs): string {
        $rel = str_replace($this->root . DIRECTORY_SEPARATOR, '', $abs);
        return str_replace(DIRECTORY_SEPARATOR, '/', $rel);
    }

    private function shouldExclude(string $rel): bool {
        if (strpos($rel, '_backups/') === 0) return true;
        if (strpos($rel, '.papelera_creawebes/') === 0) return true;
        if (strpos($rel, 'usuarios/') === 0) return true;
        if (strpos($rel, '.instalador_creawebes/') === 0) return true;
        if (strpos($rel, '_cache_') === 0) return true;
        if (substr($rel, -4) === '.zip') return true;
        return false;
    }

    private function loadState(): ?array {
        if (!file_exists($this->stateFile)) return null;
        $json = file_get_contents($this->stateFile);
        if (!$json) return null;
        return json_decode($json, true);
    }

    private function saveState(array $state): void {
        file_put_contents($this->stateFile, json_encode($state, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
    }

    private function rotateBackups(int $max = 7): void {
        $existentes = glob($this->dirBackup . '/backup_*.zip');
        usort($existentes, fn($a, $b) => filemtime($b) - filemtime($a));
        foreach (array_slice($existentes, $max) as $f) @unlink($f);
    }
}
