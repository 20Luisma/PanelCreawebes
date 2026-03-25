<?php
/**
 * restaurar_proc.php — Backend de restauración (Clean Architecture)
 * Delega toda la lógica pesada al RestoreService via RestoreController.
 */
require_once __DIR__ . '/verificar_sesion.php';

if (!$esAdmin) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

require_once __DIR__ . '/src/autoload.php';

use Infrastructure\Service\RestoreService;
use Presentation\Controller\RestoreController;

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$csrfSession = $_SESSION['csrf_restore'] ?? '';
$allowIndexOverride = $_SESSION['restore_allow_index'] ?? false;

$restoreService = new RestoreService(ROOT_DIR);
$restoreController = new RestoreController($restoreService, ROOT_DIR);

// Cerrar sesión para no bloquear requests AJAX concurrentes
session_write_close();

$restoreController->handleRequest($_POST, $csrfSession, (bool)$allowIndexOverride);

// Limpiar sesión si finalizó la restauración
$accion = $_POST['accion'] ?? '';
if ($accion === 'chunk') {
    $stateKey  = 'restore_quick_' . session_id() . '_' . sha1(realpath($restoreService->getBackupDir() . '/' . basename($_POST['zip'] ?? '')));
    $stateFile = ROOT_DIR . '/_backups/tmp/' . $stateKey . '.json';
    if (!file_exists($stateFile)) {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        unset($_SESSION['restore_allow_index']);
        session_write_close();
    }
}
