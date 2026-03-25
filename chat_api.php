<?php
session_start();

if (!isset($_SESSION['logueado']) || !isset($_SESSION['usuario'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

header('Content-Type: application/json');

// --- CLEAN ARCHITECTURE: Inyección de dependencias ---
require_once __DIR__ . '/src/autoload.php';

use Infrastructure\Persistence\FileChatRepository;
use Infrastructure\Persistence\FileOnlineUsersRepository;
use Application\UseCase\ChatUseCase;
use Presentation\Controller\ChatController;

$chatRepo   = new FileChatRepository(__DIR__ . '/mensajes.json');
$onlineRepo = new FileOnlineUsersRepository(__DIR__ . '/actividad_usuarios.json');
$chatUseCase = new ChatUseCase($chatRepo, $onlineRepo);
$chatController = new ChatController($chatUseCase);

// --- Leer entrada JSON ---
$usuarioActual = strtolower(trim($_SESSION['usuario']));
$input = json_decode(file_get_contents('php://input'), true);

// --- Delegar al controlador ---
$result = $chatController->handleRequest($usuarioActual, $input);

http_response_code($result['httpCode']);
echo json_encode($result['body']);