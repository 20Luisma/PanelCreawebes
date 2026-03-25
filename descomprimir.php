<?php
// Incluimos verificar_sesion.php que ya inicia sesión y protege el endpoint
require_once __DIR__ . '/verificar_sesion.php';

use Infrastructure\Service\ZipService;
use Application\UseCase\ZipUseCase;
use Presentation\Controller\ZipController;

// Activar dependencias
global $pathSecurityService, $esAdmin;

$zipService = new ZipService($pathSecurityService, ROOT_DIR);
$zipUseCase = new ZipUseCase($zipService, ROOT_DIR);
$zipController = new ZipController($zipUseCase);

$overrideActive = $_SESSION['override_root_index_active'] ?? false;
$overrideExpiry = $_SESSION['override_root_index_expiry'] ?? null;

$zipController->handleExtractRequest($_POST, $esAdmin, $overrideActive, $overrideExpiry);
?>
