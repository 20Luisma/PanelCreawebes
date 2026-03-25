<?php
namespace Presentation\Controller;

use Infrastructure\Service\FileEditorService;
use Exception;

class FileEditorController {
    private FileEditorService $editorService;
    private string $csrfTokenSessionKey;

    public function __construct(FileEditorService $editorService, string $csrfSessionKey = 'csrf_editor') {
        $this->editorService = $editorService;
        $this->csrfTokenSessionKey = $csrfSessionKey;
    }

    public function handleRequest(array $post, array $server, array $session, bool $isIndexProtected): ?array {
        if (($server['REQUEST_METHOD'] ?? '') !== 'POST') {
            return null; // No es POST, devolvemos null para que el editor proceda a la carga normal
        }

        // Habilitación de Override desde modal original
        if (isset($post['habilitar_override'])) {
            // Se asume validación previa CSRF en la parte legacy o la validamos acá
            if (!hash_equals($session[$this->csrfTokenSessionKey] ?? '', $post['csrf'] ?? '')) {
                return [
                    'status' => 'error_csrf',
                    'html' => "Error CSRF: Solicitud de activación de override no válida.",
                    'is_override_auth' => true
                ];
            }
            return [
                'status' => 'override_active',
                'is_override_auth' => true
            ];
        }

        // --- FLUJO DE GUARDADO (SAVE) ---
        
        // 1. Validación CSRF del Guardado
        if (!hash_equals($session[$this->csrfTokenSessionKey] ?? '', $post['csrf'] ?? '')) {
            return [
                'status' => 'error_csrf',
                'html' => "<div style='padding:2rem;font-family:sans-serif;background:#ffdede;color:#b20000;'>
                           🚫 Error CSRF: Solicitud de guardado no válida. Por favor, recargue la página.
                           <br><br><a href='index.php?carpeta=" . urlencode(dirname($post['archivo'] ?? '')) . "'>⬅️ Volver al explorador</a>
                           </div>"
            ];
        }

        $archivoRel = trim($post['archivo'] ?? '');
        $mtimeCliente = (int)($post['mtime'] ?? 0);
        $contenidoB64 = $post['contenido'] ?? '';

        // 2. Control Index Protegido Caducado (Lógica movida del script procedural)
        if ($isIndexProtected) {
            return [
                'status' => 'error_protected',
                'html' => "<div style='padding:2rem;font-family:sans-serif;background:#ffdede;color:#b20000;'>
                            🚫 No se pudo guardar. El archivo root/index.php está protegido y el override temporal ha expirado o no está activo.
                            <br><br><a href='index.php?carpeta=" . urlencode(dirname($archivoRel)) . "'>⬅️ Volver al explorador</a>
                           </div>"
            ];
        }

        // 3. Intento de guardado en el Service
        try {
             $saved = $this->editorService->saveFile($archivoRel, $contenidoB64, $mtimeCliente);
             
             if ($saved) {
                 // Éxito. Re-armamos el HTML response tal como lo espera el flujo procedural legacy
                 $rutaSub    = dirname($archivoRel);
                 if ($rutaSub === '.') $rutaSub = '';
                 
                 $nombreBase = basename($archivoRel);
                 // Buscamos el backup más reciente
                 $stamp      = date('Ymd_His');
                 $bakRel     = ($rutaSub ? "$rutaSub/" : '') . "$nombreBase/$stamp.txt";
                 
                 return [
                    'status' => 'success',
                    'bakRel' => $bakRel,
                    'html' => "<div style='padding:2rem;font-family:sans-serif;background:#d4edda;color:#155724;'>
                                ✅ Cambios guardados.<br><br>
                                <a href='download.php?archivo=" . urlencode("Historiales/" . ltrim($bakRel, '/\\')) . "' class='btn' style='
                                   background:#3949ab;color:#fff;padding:.5rem 1rem;border-radius:.3rem;
                                   font-weight:bold;text-decoration:none;'>📥 Descargar backup</a><br><br>
                                <a href='index.php?carpeta=" . urlencode(dirname($archivoRel)) . "'>⬅️ Volver al explorador</a>
                              </div>"
                 ];
             } else {
                 return [
                     'status' => 'error_save',
                     'html' => "<div style='padding:2rem;font-family:sans-serif;background:#fff3cd;color:#856404;'>
                                 ⚠️ No se pudo guardar el original.<br><br>
                                 <a href='index.php?carpeta=" . urlencode(dirname($archivoRel)) . "'>⬅️ Volver al explorador</a>
                               </div>"
                 ];
             }

        } catch (Exception $e) {
            if ($e->getCode() === 409) {
                return [
                    'status' => 'error_concurrency',
                    'html' => "<div style='padding:2rem;font-family:sans-serif;background:#fff3cd;color:#856404;'>
                               ⚠️ El archivo ha sido modificado por otra persona o proceso mientras lo editabas.
                               Tus cambios no se guardaron para evitar sobrescribir. Por favor, <strong style='color:#b20000;'>recarga la página</strong> para obtener la última versión y luego aplica tus cambios manualmente.
                               <br><br><a href='editor.php?archivo=" . urlencode($archivoRel) . "'>🔄 Recargar editor</a>
                               <br><br><a href='index.php?carpeta=" . urlencode(dirname($archivoRel)) . "'>⬅️ Volver al explorador</a>
                               </div>"
                ];
            }
            if ($e->getMessage() === 'Invalid Base64 content.') {
                return [
                    'status' => 'error_b64',
                    'html' => "<div style='padding:2rem;font-family:sans-serif;background:#ffdede;color:#b20000;'>
                               🚫 Contenido inválido (error de decodificación Base64).
                               <br><br><a href='index.php?carpeta=" . urlencode(dirname($archivoRel)) . "'>⬅️ Volver al explorador</a>
                               </div>"
                ];
            }
            
            return [
                 'status' => 'error_general',
                 'html' => "<div style='padding:2rem;font-family:sans-serif;background:#ffdede;color:#b20000;'>
                             🚫 Archivo inválido o sin permisos permitidos.
                             <br><br><a href='index.php?carpeta=" . urlencode(dirname($archivoRel)) . "'>⬅️ Volver al explorador</a>
                            </div>"
            ];
        }
    }
}
