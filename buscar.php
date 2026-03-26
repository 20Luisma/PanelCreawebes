<?php
require_once __DIR__ . '/verificar_sesion.php';
session_write_close(); // Libera el lock de la sesión para evitar blocking concurrentes //

if (empty($_SESSION['logueado'])) {
    http_response_code(403);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

require_once __DIR__ . '/src/autoload.php';

use Infrastructure\Service\FileSearchService;
use Infrastructure\Persistence\FileOnlineUsersRepository;
use Application\UseCase\SearchUseCase;
use Presentation\Controller\SearchController;

global $pathSecurityService, $esAdmin;

$archivoUsuarios = __DIR__ . '/actividad_usuarios.json';
$onlineUserRepo = new FileOnlineUsersRepository($archivoUsuarios);

$searchService = new FileSearchService($pathSecurityService);
$searchUseCase = new SearchUseCase($searchService, $pathSecurityService, $onlineUserRepo, ROOT_DIR);
$searchController = new SearchController($searchUseCase);

$usuarioLogueado = $_SESSION['usuario'] ?? null;
$overrideActive = $_SESSION['override_root_index_active'] ?? false;
$overrideExpiry = $_SESSION['override_root_index_expiry'] ?? null;

$searchController->handleRequest($_GET, $usuarioLogueado, $esAdmin, $overrideActive, $overrideExpiry);
?>
