<?php
namespace Infrastructure\Service;

class PathSecurityService {
    private string $rootDir;

    public function __construct(string $rootDir) {
        $this->rootDir = $this->normalizaRuta($rootDir);
    }

    public function normalizaRuta(string $p): string {
        $p = str_replace('\\', '/', $p);
        $p = preg_replace('#/+#', '/', $p);
        return rtrim($p, '/');
    }

    public function estaDentroDe(string $hijo, string $padre): bool {
        $hijo  = $this->normalizaRuta($hijo);
        $padre = $this->normalizaRuta($padre);
        if ($hijo === $padre) return true;
        return strpos($hijo, $padre . '/') === 0;
    }

    public function vaAQuedarComoRootIndex(string $destDirAbs, string $nombre): bool {
        $destDirCanon = realpath($destDirAbs);
        $destDirCanon = $destDirCanon !== false ? $destDirCanon : $destDirAbs;
        $destDirCanon = $this->normalizaRuta($destDirCanon);

        if (!$this->estaDentroDe($destDirCanon, $this->rootDir)) {
            return false;
        }

        $finalPath = $this->normalizaRuta($destDirCanon . '/' . basename($nombre));
        $rootIndex = $this->normalizaRuta($this->rootDir . '/index.php');

        return $finalPath === $rootIndex;
    }

    public function esRootIndex(string $absPath): bool {
        $abs = realpath($absPath);
        return $abs !== false && $this->normalizaRuta($abs) === $this->normalizaRuta($this->rootDir . '/index.php');
    }

    public function puedeTocarRootIndex(bool $esAdmin, bool $overrideActive, ?int $overrideExpiry): bool {
        if (!$esAdmin) {
            return false;
        }

        if (!$overrideActive) {
            return false;
        }

        if ($overrideExpiry === null || $overrideExpiry < time()) {
            return false;
        }

        return true;
    }
}
