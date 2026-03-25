<?php
namespace Infrastructure\Service;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ZipService {
    private PathSecurityService $security;
    private string $rootDir;

    public function __construct(PathSecurityService $security, string $rootDir) {
        $this->security = $security;
        $this->rootDir = $rootDir;
    }

    public function compressFiles(array $archivosRelativos, string $rutaDestinoAbs, string $nombreZipDeseado): array {
        if (empty($archivosRelativos)) {
            return ['ok' => false, 'error' => "No se ha especificado ningún archivo para comprimir."];
        }

        if (!$this->security->estaDentroDe($rutaDestinoAbs, $this->rootDir)) {
            return ['ok' => false, 'error' => "Ruta de destino inválida o fuera del directorio permitido."];
        }

        $nombreBase = pathinfo($nombreZipDeseado, PATHINFO_FILENAME);
        $extension = pathinfo($nombreZipDeseado, PATHINFO_EXTENSION) ?: 'zip';
        $contador = 1;
        $nombreFinalZip = $nombreZipDeseado;

        while (file_exists($rutaDestinoAbs . '/' . $nombreFinalZip)) {
            $nombreFinalZip = $nombreBase . '_' . $contador . '.' . $extension;
            $contador++;
        }
        $rutaCompletaZip = $rutaDestinoAbs . '/' . $nombreFinalZip;

        $zip = new ZipArchive();
        if ($zip->open($rutaCompletaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            return ['ok' => false, 'error' => "No se pudo crear el archivo ZIP en la ruta especificada."];
        }

        $root = $this->rootDir;
        foreach ($archivosRelativos as $archivoRel) {
            $rutaAbsolutaArchivo = realpath($root . '/' . $archivoRel);
            if (!$rutaAbsolutaArchivo || strpos($rutaAbsolutaArchivo, $root) !== 0 || !file_exists($rutaAbsolutaArchivo)) {
                continue;
            }

            if (is_dir($rutaAbsolutaArchivo)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($rutaAbsolutaArchivo, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = basename($rutaAbsolutaArchivo) . '/' . substr($filePath, strlen($rutaAbsolutaArchivo) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
            } else {
                $zip->addFile($rutaAbsolutaArchivo, basename($rutaAbsolutaArchivo));
            }
        }

        $zip->close();
        return ['ok' => true, 'nombre_final' => $nombreFinalZip];
    }

    public function extractZip(string $rutaZipAbs, string $rutaDestinoAbs, bool $esAdmin, bool $overrideActive, ?int $overrideExpiry): array {
        if (!class_exists('ZipArchive')) {
            return ['ok' => false, 'error' => "Extensión ZIP no disponible en el servidor."];
        }

        if (!$rutaZipAbs || !is_file($rutaZipAbs) || strtolower(pathinfo($rutaZipAbs, PATHINFO_EXTENSION)) !== 'zip' || !$this->security->estaDentroDe($rutaZipAbs, $this->rootDir)) {
            return ['ok' => false, 'error' => "Archivo ZIP inválido, no existe o no está permitido."];
        }

        $dirDestinoCanon = realpath($rutaDestinoAbs);
        $dirDestinoCanon = ($dirDestinoCanon !== false) ? $dirDestinoCanon : $rutaDestinoAbs;
        if (!$this->security->estaDentroDe($dirDestinoCanon, $this->rootDir)) {
            return ['ok' => false, 'error' => "La ruta de destino está fuera del directorio permitido."];
        }

        $zip = new ZipArchive();
        if ($zip->open($rutaZipAbs) !== TRUE) {
            return ['ok' => false, 'error' => "No se pudo abrir el archivo ZIP."];
        }

        $mensajes = [];
        $maxEntries = 50000;
        $maxUncomp  = 5 * 1024**3; // 5 GB

        if ($zip->numFiles > $maxEntries) {
            $zip->close();
            return ['ok' => false, 'error' => "El ZIP contiene demasiados archivos ({$zip->numFiles}). Límite: {$maxEntries}."];
        }

        $currentUncompressedSize = 0;
        $root = $this->rootDir;

        // Limites para evitar desbordes de tiempo
        ignore_user_abort(true);
        set_time_limit(0);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if ($entryName === false) {
                $mensajes[] = "❌ Error: No se pudo obtener la entrada para el índice {$i}";
                continue;
            }

            $st = $zip->statIndex($i);
            if ($st === false) {
                $mensajes[] = "❌ Error: No se pudo obtener la información de la entrada: {$entryName}";
                continue;
            }

            $currentUncompressedSize += ($st['size'] ?? 0);
            if ($currentUncompressedSize > $maxUncomp) {
                $zip->close();
                return ['ok' => false, 'error' => "El tamaño total descomprimido excede el límite de 5 GB."];
            }

            $cleanEntryName = $this->security->normalizaRuta($entryName);
            if (empty($cleanEntryName) && $entryName !== '/') {
                $mensajes[] = "⚠️ Omitida entrada con ruta vacía/no válida: {$entryName}";
                continue;
            }
            if (strpos($cleanEntryName, '/') === 0 || strpos($cleanEntryName, '../') === 0) {
                $mensajes[] = "⚠️ Omitida por posible path traversal: {$entryName}";
                continue;
            }

            $mode = ($st['external_attributes'] ?? 0) >> 16;
            $isSymlink = ($mode & 0xF000) === 0xA000;
            if ($isSymlink) {
                $mensajes[] = "⚠️ Omitido symlink: {$entryName}";
                continue;
            }

            $isDirEntry = (substr($entryName, -1) === '/');
            $rutaCompletaDestino = $dirDestinoCanon . '/' . $cleanEntryName;

            if ($this->security->vaAQuedarComoRootIndex(dirname($rutaCompletaDestino), basename($rutaCompletaDestino))) {
                if (!$this->security->puedeTocarRootIndex($esAdmin, $overrideActive, $overrideExpiry)) {
                    $mensajes[] = "🚫 Omitido {$entryName}: root/index.php está protegido (sin override).";
                    continue;
                }
            }

            if ($isDirEntry || (isset($st['size']) && $st['size'] === 0 && substr($entryName, -1) === '/')) {
                if (!$this->security->estaDentroDe($rutaCompletaDestino, $root)) {
                    $mensajes[] = "⚠️ Omitido directorio fuera de raíz: {$entryName}";
                    continue;
                }
                if (!is_dir($rutaCompletaDestino)) {
                    if (mkdir($rutaCompletaDestino, 0755, true)) {
                        $mensajes[] = "✅ Directorio creado: {$entryName}";
                    } else {
                        $mensajes[] = "❌ No se pudo crear directorio: {$entryName}";
                    }
                } else {
                    $mensajes[] = "ℹ️ Directorio ya existía: {$entryName}";
                }
                continue;
            }

            $dirPadreArchivo = dirname($rutaCompletaDestino);
            if (!is_dir($dirPadreArchivo)) {
                if (!$this->security->estaDentroDe($dirPadreArchivo, $root)) {
                    $mensajes[] = "⚠️ Omitido archivo con directorio padre fuera de raíz: {$entryName}";
                    continue;
                }
                if (!mkdir($dirPadreArchivo, 0755, true)) {
                    $mensajes[] = "❌ No se pudo crear directorio padre para: {$entryName}";
                    continue;
                }
            }

            $realParentDir = realpath($dirPadreArchivo);
            if ($realParentDir === false || !$this->security->estaDentroDe($realParentDir, $root)) {
                $mensajes[] = "⚠️ Omitido por destino inválido/fuera de raíz tras normalización: {$entryName}";
                continue;
            }

            $nombreBaseArchivo = basename($cleanEntryName);
            $rutaFinalGuardar  = $realParentDir . '/' . $nombreBaseArchivo;
            $contador = 1;
            while (file_exists($rutaFinalGuardar)) {
                $info = pathinfo($nombreBaseArchivo);
                $nuevoNombre = $info['filename'] . '_' . $contador . (isset($info['extension']) ? '.' . $info['extension'] : '');
                $rutaFinalGuardar = $realParentDir . '/' . $nuevoNombre;
                $contador++;
            }
            $aviso = ($rutaFinalGuardar !== $realParentDir . '/' . $nombreBaseArchivo) ? " (existía, guardado como " . basename($rutaFinalGuardar) . ")" : "";

            $srcStream = @fopen("zip://{$rutaZipAbs}#{$entryName}", 'rb');
            if ($srcStream === false) {
                $mensajes[] = "❌ Error abriendo stream del ZIP: {$entryName}";
                continue;
            }

            $dstStream = @fopen($rutaFinalGuardar, 'wb');
            if ($dstStream === false) {
                fclose($srcStream);
                $mensajes[] = "❌ No se pudo abrir destino para escribir: {$entryName}";
                continue;
            }

            $copied = stream_copy_to_stream($srcStream, $dstStream);
            fclose($srcStream);
            fclose($dstStream);

            if ($copied === false) {
                @unlink($rutaFinalGuardar);
                $mensajes[] = "❌ Falló la copia por streaming: {$entryName}";
                continue;
            }

            chmod($rutaFinalGuardar, 0644);
            $mensajes[] = "✅ Extraído: {$entryName}{$aviso}";
        }

        $zip->close();
        return ['ok' => true, 'mensajes' => $mensajes];
    }
}
