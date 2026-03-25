<?php
require_once __DIR__ . '/verificar_sesion.php';

if (empty($_SESSION['logueado'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=UTF-8');
    exit("Acceso denegado: Por favor, inicie sesión.");
}

require_once __DIR__ . '/src/autoload.php';

use Infrastructure\Service\DownloadService;
use Application\UseCase\DownloadUseCase;
use Presentation\Controller\DownloadController;

global $pathSecurityService, $esAdmin;

$downloadService = new DownloadService($pathSecurityService, ROOT_DIR);
$downloadUseCase = new DownloadUseCase($downloadService, $pathSecurityService, ROOT_DIR);
$downloadController = new DownloadController($downloadUseCase, $downloadService);

$overrideActive = $_SESSION['override_root_index_active'] ?? false;
$overrideExpiry = $_SESSION['override_root_index_expiry'] ?? null;

$downloadController->handleRequest($_GET, $esAdmin, $overrideActive, $overrideExpiry);
?>
