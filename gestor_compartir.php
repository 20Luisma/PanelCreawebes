<?php
// Incluimos el gestor de sesión para saber quién está logueado
require_once __DIR__ . '/verificar_sesion.php';
require_once __DIR__ . '/src/autoload.php';

use Infrastructure\Persistence\FileShareRepository;
use Infrastructure\Persistence\FileOnlineUsersRepository;
use Application\UseCase\ShareManagementUseCase;
use Presentation\Controller\ShareController;

$archivoCompartidos = __DIR__ . '/compartidos.json';
$archivoUsuarios = __DIR__ . '/actividad_usuarios.json';

$shareRepo = new FileShareRepository($archivoCompartidos);
$onlineUserRepo = new FileOnlineUsersRepository($archivoUsuarios);
$shareUseCase = new ShareManagementUseCase($shareRepo, $onlineUserRepo);
$shareController = new ShareController($shareUseCase);

$usuarioLogueado = $_SESSION['usuario'] ?? null;
$esAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;

// Leer payload crudo al ser application/json desde fetch()
$data = json_decode(file_get_contents('php://input'), true);

$shareController->handleRequest($data ?: [], $usuarioLogueado, $esAdmin);
?>