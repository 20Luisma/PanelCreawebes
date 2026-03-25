<?php
namespace Infrastructure\Service;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class FileSearchService {
    private PathSecurityService $security;

    // Archivos y carpetas de sistema que el explorador oculta en la UI.
    // Deben coincidir con $archivosSistema de index.php
    private const HIDDEN_ITEMS = [
        '_backups', 'Historiales', '_informes_restore', '.instalador_creawebes',
        'src', 'autoload.php', 'vendor',
        '.htaccess', 'archivocrear.php', 'backup.php', 'backup_cron.php',
        'backup_proc.php', 'buscar.php', 'conectados.php', 'usuarios_activos.json',
        'funciones.php', 'verificar_sesion.php', 'comprimir.php',
        'CrearNuevo Archivo.php', 'crear_hash.php', 'default.php', 'latido.php',
        'api_carpetas.php', 'api_contador.php', 'descomprimir.php', 'download.php',
        'editor.php', 'usuarios.php', '.usuarios_online.json', 'actividad_usuarios.json',
        'mensajes.json', 'chat_api.php', 'compartidos.json', 'gestor_compartir.php',
        'google66cdb90433076f9f.html', 'index.php', 'login.php', 'logout.php',
        '.papelera_creawebesindex.php', 'preview.php', 'gestionar_usuarios.php',
        'readme.txt', 'restaurar.php', 'restaurar_proc.php', 'restaurar_proc_completo.php',
        'sitemap.xml', 'test_cron.php', 'llamada.mp3', 'relampago.mp3',
        'notificacion.mp3', 'ver_backups.php',
    ];

    // Carpetas de sistema cuyo contenido interno también debe ocultarse
    private const HIDDEN_DIRS = [
        '_backups', 'Historiales', '_informes_restore', '.instalador_creawebes',
        'src', 'vendor', '.papelera_creawebes',
    ];

    public function __construct(PathSecurityService $security) {
        $this->security = $security;
    }

    private function isInsideHiddenDir(string $rutaRelativa): bool {
        foreach (self::HIDDEN_DIRS as $dir) {
            if (str_starts_with($rutaRelativa, $dir . '/')) {
                return true;
            }
        }
        return false;
    }

    public function searchDirectory(string $baseDirAbs, string $query, string $rootDir, bool $esAdmin, bool $overrideActive, ?int $overrideExpiry): array {
        if (!file_exists($baseDirAbs) || !is_dir($baseDirAbs)) {
            return [];
        }

        $resultados = [];
        $lenBase = strlen($rootDir) + 1;

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDirAbs, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $puedeTocarRootIndex = $this->security->puedeTocarRootIndex($esAdmin, $overrideActive, $overrideExpiry);

        foreach ($it as $file) {
            $filePath = $file->getRealPath();
            if ($filePath === false || $file->isLink()) continue;

            $rutaRel = substr($filePath, $lenBase);
            $rutaRel = $this->security->normalizaRuta($rutaRel);
            $basename = $file->getFilename();

            // Filtrar archivos/carpetas ocultos del sistema SOLO para no-admins
            if (!$esAdmin) {
                if (in_array($basename, self::HIDDEN_ITEMS, true)) continue;
                if ($this->isInsideHiddenDir($rutaRel)) continue;
            }

            $filename = strtolower($basename);
            if (stripos($filename, $query) !== false) {
                if (is_file($filePath) && $this->security->esRootIndex($filePath, $rootDir) && !$puedeTocarRootIndex) {
                    continue;
                }

                $resultados[] = [
                    'nombre' => $basename,
                    'ruta'   => $rutaRel,
                    'tipo'   => $file->isDir() ? 'carpeta' : 'archivo'
                ];
            }
        }

        return $resultados;
    }
}
