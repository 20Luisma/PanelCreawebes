<?php
namespace Infrastructure\Service;

use ZipArchive;

class RestoreService {
    private string $root;
    private string $backupDir;
    private string $informesDir;
    private string $tmpDir;
    private string $logFile;

    private array $excludePrefixes = [
        '_backups',
        '.instalador_creawebes',
        '.papelera_creawebes',
    ];

    const CHUNK_SIZE  = 400;
    const MAX_ENTRIES = 200000;
    const MAX_UNCOMP  = 30 * 1024 * 1024 * 1024; // 30GB

    public function __construct(string $root) {
        $this->root        = rtrim(str_replace('\\', '/', realpath($root)), '/');
        $this->backupDir   = $this->root . '/_backups/registros';
        $this->informesDir = $this->root . '/_backups/informes';
        $this->tmpDir      = $this->root . '/_backups/tmp';

        @is_dir($this->informesDir) || @mkdir($this->informesDir, 0775, true);
        @is_dir($this->tmpDir)      || @mkdir($this->tmpDir,      0775, true);

        $this->logFile = $this->informesDir . '/restore_last.log';
    }

    public function getBackupDir(): string {
        return $this->backupDir;
    }

    public function init(string $zipAbs, bool $allowIndexOverride, string $stateFile, array $excludeExact): array {
        if (!class_exists('ZipArchive')) return ['error' => 'ZipArchive no disponible'];

        $zip = new ZipArchive;
        if ($zip->open($zipAbs) !== TRUE) return ['error' => 'No se pudo abrir ZIP'];

        $n = $zip->numFiles;
        if ($n > self::MAX_ENTRIES) { $zip->close(); return ['error' => "Demasiadas entradas: $n"]; }

        $sum = 0;
        for ($i = 0; $i < $n; $i++) {
            $st = $zip->statIndex($i);
            if ($st && isset($st['size'])) {
                $sum += (int)$st['size'];
                if ($sum > self::MAX_UNCOMP) { $zip->close(); return ['error' => 'Tamaño estimado excede límite']; }
            }
        }

        $list = [];
        for ($i = 0; $i < $n; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) continue;

            $isDir = (substr($name, -1) === '/');
            $base  = rtrim($name, '/');
            if ($base === '') continue;

            if (!$this->isSafeZipName($name)) { $list[] = ['name' => $name, 'skip' => 'path_suspect']; continue; }

            $clean  = $this->norm($name);
            $cleanL = ltrim($clean, '/');

            if (preg_match('/\.zip$/i', $cleanL)) { $list[] = ['name' => $name, 'skip' => 'zip']; continue; }

            $first = explode('/', $cleanL, 2)[0];
            if (in_array($first, $this->excludePrefixes, true)) { $list[] = ['name' => $name, 'skip' => 'protegido']; continue; }
            if (in_array($cleanL, $excludeExact, true)) { $list[] = ['name' => $name, 'skip' => 'self']; continue; }

            if ($clean === 'usuarios' || str_starts_with($clean, 'usuarios/')) {
                $list[] = ['name' => $name, 'skip' => 'usuarios']; continue;
            }
            if ($this->isMetaUser($clean)) { $list[] = ['name' => $name, 'skip' => 'meta_usuarios']; continue; }

            $st = $zip->statIndex($i) ?: [];
            $mode = ($st['external_attributes'] ?? 0) >> 16;
            if (($mode & 0xF000) === 0xA000) { $list[] = ['name' => $name, 'skip' => 'symlink']; continue; }

            $list[] = ['name' => $name, 'skip' => null, 'isDir' => $isDir];
        }
        $zip->close();

        $state = ['zip' => $zipAbs, 'idx' => 0, 'total' => count($list), 'list' => $list, 'override' => $allowIndexOverride];
        @file_put_contents($stateFile, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->logRestore("INIT ok: total={$state['total']} zip=" . basename($zipAbs));
        return ['total' => $state['total'], 'finalizado' => false];
    }

    public function chunk(string $stateFile): array {
        if (!is_file($stateFile)) return ['error' => 'Estado no encontrado'];
        $state = json_decode(@file_get_contents($stateFile), true);
        if (!$state || empty($state['list']) || empty($state['zip'])) return ['error' => 'Estado corrupto'];

        if (!class_exists('ZipArchive')) return ['error' => 'ZipArchive no disponible'];
        $zip = new ZipArchive;
        if ($zip->open($state['zip']) !== TRUE) return ['error' => 'No se pudo abrir ZIP (chunk)'];

        $inicio = (int)$state['idx'];
        $fin    = min($inicio + self::CHUNK_SIZE - 1, $state['total'] - 1);

        $namesToExtract = [];
        $dirsCreated    = [];

        for ($i = $inicio; $i <= $fin; $i++) {
            $e     = $state['list'][$i];
            $name  = $e['name'];
            $clean = $this->norm($name);
            $isDir = isset($e['isDir']) ? (bool)$e['isDir'] : (substr($name, -1) === '/');

            if ($e['skip']) continue;
            if ($clean === '') continue;

            $dest = $this->root . '/' . $clean;
            if (!$state['override'] && $this->isRootIndex($dest)) continue;

            if ($isDir) {
                $destDir = rtrim($dest, '/');
                if (!isset($dirsCreated[$destDir]) && !is_dir($destDir)) {
                    @mkdir($destDir, 0755, true);
                    $dirsCreated[$destDir] = true;
                }
                continue;
            }

            $namesToExtract[] = $name;
        }

        if (!empty($namesToExtract)) {
            $ok = $zip->extractTo($this->root, $namesToExtract);
            if (!$ok) $this->logRestore("extractTo parcial falló en tanda ($inicio-$fin)");
        }

        $zip->close();

        $state['idx'] = $fin + 1;
        @file_put_contents($stateFile, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $done = ($state['idx'] >= $state['total']);
        if ($done) {
            @unlink($stateFile);
            $this->logRestore("DONE: procesados={$state['total']}");
        }

        return ['finalizado' => $done, 'procesados' => $state['idx'], 'logs' => []];
    }

    // --- Helpers privados ---

    private function norm(string $p): string {
        $p = str_replace('\\', '/', $p);
        $parts = explode('/', $p); $out = [];
        foreach ($parts as $x) {
            if ($x === '' || $x === '.') continue;
            if ($x === '..') { array_pop($out); continue; }
            $out[] = $x;
        }
        return implode('/', $out);
    }

    private function isSafeZipName(string $name): bool {
        if ($name === '') return false;
        if ($name[0] === '/' || $name[0] === '\\') return false;
        if (strpos($name, "\\") !== false) return false;
        $clean = $this->norm($name);
        return $clean === rtrim($name, '/');
    }

    private function isMetaUser(string $clean): bool {
        $clean = ltrim($this->norm($clean), '/');
        return in_array($clean, [
            'usuarios.php', 'usuarios.json', 'usuario.json', 'actividad_usuarios.json',
            'usuarios/usuarios.php', 'usuarios/usuarios.json', 'usuarios/usuario.json', 'usuarios/actividad_usuarios.json',
        ], true);
    }

    private function isRootIndex(string $abs): bool {
        $abs = str_replace('\\', '/', $abs);
        return strcasecmp($abs, $this->root . '/index.php') === 0;
    }

    private function logRestore(string $msg): void {
        @file_put_contents($this->logFile, '[' . date('H:i:s') . "] $msg\n", FILE_APPEND);
    }
}
