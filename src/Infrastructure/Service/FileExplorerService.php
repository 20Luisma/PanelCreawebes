<?php
namespace Infrastructure\Service;

/**
 * FileExplorerService – Acciones del explorador de archivos.
 * Extrae toda la lógica del switch POST de index.php.
 */
class FileExplorerService {
    private string $root;
    private string $papelera;

    public function __construct(string $root) {
        $this->root     = $root;
        $this->papelera = $root . '/.papelera_creawebes';
        if (!is_dir($this->papelera)) {
            mkdir($this->papelera, 0775, true);
            file_put_contents($this->papelera . '/.htaccess', "Deny from all");
        }
    }

    // --- Acciones principales ---

    public function upload(string $rutaActual, array $fileData, bool $forzar = false): array {
        if ($fileData['error'] !== UPLOAD_ERR_OK) return ['error' => 'Error en la subida'];
        $nombre = basename($fileData['name']);
        $destino = $rutaActual . '/' . $nombre;
        if ($this->isRootIndex($destino) && !$this->canTouchRootIndex()) {
            return ['error' => 'protegido_index'];
        }
        // Si ya existe y no se forzó el overwrite, avisar al frontend
        if (file_exists($destino) && !$forzar) {
            return ['error' => 'conflicto', 'archivo' => $nombre];
        }
        move_uploaded_file($fileData['tmp_name'], $destino);
        return ['ok' => true];
    }


    public function delete(string $rutaObjAbs, string $carpetaRelativa): array {
        if ($this->isRootIndex($rutaObjAbs) && !$this->canTouchRootIndex()) {
            return ['error' => 'protegido_index'];
        }

        if (str_contains($rutaObjAbs, '/.papelera_creawebes')) {
            is_file($rutaObjAbs) ? unlink($rutaObjAbs) : $this->deleteRecursive($rutaObjAbs);
            return ['ok' => true, 'redirect' => '.papelera_creawebes'];
        }

        $destino = $this->papelera . '/' . basename($rutaObjAbs);
        if (file_exists($destino)) {
            $destino = $this->papelera . '/' . time() . '_' . basename($rutaObjAbs);
        }
        rename($rutaObjAbs, $destino);

        $registro = [
            'original'  => $this->getRelativePath($rutaObjAbs),
            'eliminado' => $this->getRelativePath($destino),
            'fecha'     => date('Y-m-d H:i:s'),
        ];
        file_put_contents(
            $this->papelera . '/registros.json',
            json_encode($registro, JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND
        );

        $parentDir = dirname($rutaObjAbs);
        return ['ok' => true, 'redirect' => $this->getRelativePath($parentDir)];
    }

    public function rename(string $rutaObjAbs, string $nuevoNombre, string $carpetaRelativa): array {
        $nuevoNombre = trim(basename($nuevoNombre));
        if ($nuevoNombre === '') return ['error' => 'nombre_vacio'];

        $extOriginal = pathinfo($rutaObjAbs, PATHINFO_EXTENSION);
        if (strpos($nuevoNombre, '.') === false && $extOriginal !== '') {
            $nuevoNombre .= '.' . $extOriginal;
        }

        $destino = dirname($rutaObjAbs) . '/' . $nuevoNombre;

        if (($this->isRootIndex($rutaObjAbs) || $this->isRootIndex($destino)) && !$this->canTouchRootIndex()) {
            return ['error' => 'protegido_index'];
        }

        if (file_exists($destino)) return ['error' => 'existe'];
        rename($rutaObjAbs, $destino);
        return ['ok' => true, 'redirect' => $carpetaRelativa];
    }

    public function duplicate(string $rutaObjAbs, string $carpetaRelativa): array {
        $nombre = basename($rutaObjAbs);
        $dir    = dirname($rutaObjAbs);
        $copia  = $dir . '/copia_' . $nombre;

        if (($this->isRootIndex($rutaObjAbs) || $this->isRootIndex($copia)) && !$this->canTouchRootIndex()) {
            return ['error' => 'protegido_index'];
        }

        if (file_exists($copia)) return ['error' => 'existe'];
        is_file($rutaObjAbs) ? copy($rutaObjAbs, $copia) : $this->duplicateFolder($rutaObjAbs, $copia);
        return ['ok' => true, 'redirect' => $this->getRelativePath($dir)];
    }

    public function createFolder(string $rutaActual, string $nombre): array {
        $nombre = basename($nombre);
        $ruta = $rutaActual . '/' . $nombre;
        if (file_exists($ruta)) return ['error' => 'existe'];
        mkdir($ruta);
        return ['ok' => true];
    }

    public function createFile(string $rutaActual, string $nombre, string $contenido): array {
        $nombre = basename($nombre);
        if ($this->isRootIndex($rutaActual . '/' . $nombre) && !$this->canTouchRootIndex()) {
            return ['error' => 'protegido_index'];
        }
        if (file_exists($rutaActual . '/' . $nombre)) return ['error' => 'existe'];
        file_put_contents($rutaActual . '/' . $nombre, $contenido);
        return ['ok' => true];
    }

    public function move(string $rutaObjAbs, string $destinoRel, bool $forzar, string $carpetaRelativa): array {
        $rutaDestAbs = $destinoRel === '' ? $this->root : realpath($this->root . '/' . $destinoRel);
        $nombre = basename($rutaObjAbs);

        if (!$rutaDestAbs || !$this->isWithinRoot($rutaDestAbs)) {
            return ['error' => 'ruta_invalida'];
        }
        if (($this->isRootIndex($rutaObjAbs) || $this->isRootIndex($rutaDestAbs . '/' . $nombre)) && !$this->canTouchRootIndex()) {
            return ['error' => 'protegido_index'];
        }

        if (realpath($rutaDestAbs) !== realpath(dirname($rutaObjAbs))) {
            $nuevaRuta = $rutaDestAbs . '/' . $nombre;
            if (file_exists($nuevaRuta) && !$forzar) {
                return ['error' => 'conflicto', 'archivo' => $this->getRelativePath($rutaObjAbs), 'destino' => $destinoRel];
            }
            if (file_exists($nuevaRuta) && $forzar) {
                $this->deleteRecursive($nuevaRuta);
            }
            rename($rutaObjAbs, $nuevaRuta);
            return ['ok' => true, 'redirect' => $destinoRel];
        }
        return ['ok' => true, 'redirect' => $carpetaRelativa];
    }

    public function emptyTrash(string $clave, string $carpetaRelativa): array {
        $passwordPapelera = $_ENV['PAPELERA_PASSWORD'] ?? '';
        if ($clave !== $passwordPapelera) return ['error' => 'clave'];

        $registrosPath = $this->papelera . '/registros.json';
        if (file_exists($registrosPath) && !$this->canTouchRootIndex()) {
            $lineas = file($registrosPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lineas as $linea) {
                $r = json_decode($linea, true);
                if (!empty($r['original'])) {
                    $origAbs = $this->root . '/' . ltrim($r['original'], '/');
                    if ($this->isRootIndex($origAbs)) {
                        return ['error' => 'protegido_index'];
                    }
                }
            }
        }

        $items = array_diff(scandir($this->papelera), ['.', '..', '.htaccess', 'registros.json']);
        foreach ($items as $i) {
            $ruta = $this->papelera . '/' . $i;
            is_file($ruta) ? unlink($ruta) : $this->deleteRecursive($ruta);
        }
        return ['ok' => 'papelera_vaciada', 'redirect' => $carpetaRelativa];
    }

    public function restoreFromTrash(string $archivoRel, string $carpetaRelativa): array {
        $archivoAbs = realpath($this->root . '/' . $archivoRel);
        if (!$archivoAbs || !$this->isWithinRoot($archivoAbs) || !str_contains($archivoAbs, '.papelera_creawebes')) {
            return ['error' => 'ruta_invalida'];
        }

        $registrosPath = $this->papelera . '/registros.json';
        if (!file_exists($registrosPath)) return ['error' => 'sin_registros'];

        $lineas = file($registrosPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $nuevasLineas = [];
        $restaurado = false;

        foreach ($lineas as $linea) {
            $reg = json_decode($linea, true);
            if (isset($reg['eliminado']) && $reg['eliminado'] === $archivoRel) {
                $destAbs = $this->root . '/' . $reg['original'];
                if ($this->isRootIndex($destAbs) && !$this->canTouchRootIndex()) {
                    return ['error' => 'protegido_index'];
                }
                $dirDest = dirname($destAbs);
                if (!is_dir($dirDest)) mkdir($dirDest, 0777, true);
                $destAbs = $this->resolveConflictOnRestore($destAbs);
                if (rename($archivoAbs, $destAbs)) {
                    $restaurado = true;
                } else {
                    $nuevasLineas[] = $linea;
                }
            } else {
                $nuevasLineas[] = $linea;
            }
        }

        if ($restaurado) {
            file_put_contents($registrosPath, implode(PHP_EOL, $nuevasLineas) . PHP_EOL);
        }
        return ['ok' => true, 'redirect' => $carpetaRelativa];
    }

    public function deleteMultiple(array $archivos, string $carpetaRelativa): array {
        $redir = $carpetaRelativa;
        if (!empty($archivos)) {
            $primer = realpath($this->root . '/' . $archivos[0]);
            if ($primer && $this->isWithinRoot($primer)) {
                $redir = $this->getRelativePath(dirname($primer));
            }
        }

        foreach ($archivos as $rel) {
            $abs = realpath($this->root . '/' . $rel);
            if (!$abs || !$this->isWithinRoot($abs)) continue;
            if ($this->isRootIndex($abs) && !$this->canTouchRootIndex()) continue;

            if (str_contains($abs, '/.papelera_creawebes')) {
                is_file($abs) ? unlink($abs) : $this->deleteRecursive($abs);
                $redir = '.papelera_creawebes';
            } else {
                $destino = $this->papelera . '/' . basename($abs);
                if (file_exists($destino)) $destino = $this->papelera . '/' . time() . '_' . basename($abs);
                rename($abs, $destino);
                $registro = ['original' => $this->getRelativePath($abs), 'eliminado' => $this->getRelativePath($destino), 'fecha' => date('Y-m-d H:i:s')];
                file_put_contents($this->papelera . '/registros.json', json_encode($registro, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
            }
        }
        return ['ok' => true, 'redirect' => $redir];
    }

    public function restoreMultiple(array $archivos, string $carpetaRelativa): array {
        $registrosPath = $this->papelera . '/registros.json';
        $lineas = file_exists($registrosPath) ? file($registrosPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $nuevasLineas = $lineas;

        foreach ($archivos as $archivoRel) {
            $archivoAbs = realpath($this->root . '/' . $archivoRel);
            if (!$archivoAbs || !str_contains($archivoAbs, '.papelera_creawebes')) continue;

            $pending = [];
            $ok = false;
            foreach ($nuevasLineas as $linea) {
                $reg = json_decode($linea, true);
                if (isset($reg['eliminado']) && $reg['eliminado'] === $archivoRel) {
                    $destAbs = $this->root . '/' . $reg['original'];
                    if ($this->isRootIndex($destAbs) && !$this->canTouchRootIndex()) {
                        $pending[] = $linea; continue;
                    }
                    $dirDest = dirname($destAbs);
                    if (!is_dir($dirDest)) mkdir($dirDest, 0777, true);
                    $destAbs = $this->resolveConflictOnRestore($destAbs);
                    if (rename($archivoAbs, $destAbs)) { $ok = true; } else { $pending[] = $linea; }
                } else { $pending[] = $linea; }
            }
            if ($ok) $nuevasLineas = $pending;
        }

        file_put_contents($registrosPath, implode(PHP_EOL, $nuevasLineas) . (empty($nuevasLineas) ? "" : PHP_EOL));
        return ['ok' => 'restaurados', 'redirect' => $carpetaRelativa];
    }

    public function moveMultiple(array $archivos, string $destinoRel, bool $forzar, string $carpetaRelativa): array {
        $rutaDestAbs = $destinoRel === '' ? $this->root : realpath($this->root . '/' . $destinoRel);
        if (!$rutaDestAbs || !$this->isWithinRoot($rutaDestAbs)) {
            return ['error' => 'ruta_invalida'];
        }

        $conflictos = [];
        $aMovar     = [];

        foreach ($archivos as $rel) {
            $origenAbs = realpath($this->root . '/' . $rel);
            if (!$origenAbs || !$this->isWithinRoot($origenAbs)) continue;
            $nombre  = basename($rel);
            $destAbs = $rutaDestAbs . '/' . $nombre;

            if (($this->isRootIndex($origenAbs) || $this->isRootIndex($destAbs)) && !$this->canTouchRootIndex()) continue;

            if (file_exists($destAbs) && $origenAbs !== $destAbs && !$forzar) {
                $conflictos[] = $rel;
            } else {
                $aMovar[] = ['origen' => $origenAbs, 'destino' => $destAbs];
            }
        }

        if (!empty($conflictos) && !$forzar) {
            return ['error' => 'conflicto_multiple', 'archivos' => $conflictos, 'destino' => $destinoRel];
        }

        foreach ($aMovar as $item) {
            if ($item['origen'] !== $item['destino']) {
                if (file_exists($item['destino'])) $this->deleteRecursive($item['destino']);
                rename($item['origen'], $item['destino']);
            }
        }
        return ['ok' => true, 'redirect' => $destinoRel];
    }

    // --- Helpers privados ---

    private function getRelativePath(string $abs): string {
        return ltrim(str_replace($this->root, '', $abs), '/\\');
    }

    private function isWithinRoot(string $path): bool {
        return strpos(realpath($path) ?: $path, realpath($this->root)) === 0;
    }

    private function isRootIndex(string $path): bool {
        $rootNorm = rtrim(str_replace('\\', '/', $this->root), '/');
        $pathNorm = str_replace('\\', '/', $path);
        return strcasecmp($pathNorm, $rootNorm . '/index.php') === 0;
    }

    private function canTouchRootIndex(): bool {
        return !empty($_SESSION['override_index']);
    }

    private function deleteRecursive(string $ruta): void {
        if (is_dir($ruta) && !is_link($ruta)) {
            foreach (scandir($ruta) as $i) {
                if ($i === '.' || $i === '..') continue;
                $this->deleteRecursive("$ruta/$i");
            }
            rmdir($ruta);
        } elseif (file_exists($ruta)) {
            unlink($ruta);
        }
    }

    private function duplicateFolder(string $src, string $dst): void {
        mkdir($dst);
        foreach (scandir($src) as $i) {
            if ($i === '.' || $i === '..') continue;
            $s = "$src/$i"; $d = "$dst/$i";
            is_dir($s) ? $this->duplicateFolder($s, $d) : copy($s, $d);
        }
    }

    private function resolveConflictOnRestore(string $destAbs): string {
        if (!file_exists($destAbs)) return $destAbs;
        $base = pathinfo($destAbs, PATHINFO_FILENAME);
        $ext  = pathinfo($destAbs, PATHINFO_EXTENSION);
        $dir  = dirname($destAbs);
        $c = 1;
        while (file_exists($destAbs)) {
            $destAbs = $dir . '/' . $base . '_restaurado' . $c . ($ext ? '.' . $ext : '');
            $c++;
        }
        return $destAbs;
    }
}
