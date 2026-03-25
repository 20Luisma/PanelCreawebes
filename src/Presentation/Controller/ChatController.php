<?php
namespace Presentation\Controller;

use Application\UseCase\ChatUseCase;

class ChatController {
    private ChatUseCase $chatUseCase;

    public function __construct(ChatUseCase $chatUseCase) {
        $this->chatUseCase = $chatUseCase;
    }

    /**
     * Maneja la petición JSON del chat y devuelve la respuesta como array.
     */
    public function handleRequest(string $currentUser, ?array $input): array {
        if (!is_array($input)) {
            return ['httpCode' => 400, 'body' => ['ok' => false, 'error' => 'Petición JSON inválida']];
        }

        $action = $input['accion'] ?? null;

        switch ($action) {
            case 'enviar':
                $to   = strtolower(trim($input['para'] ?? ''));
                $text = trim($input['texto'] ?? '');
                if (!$to || !$text) {
                    return ['httpCode' => 400, 'body' => ['ok' => false, 'error' => 'Faltan datos (destinatario o texto)']];
                }
                return ['httpCode' => 200, 'body' => $this->chatUseCase->sendMessage($currentUser, $to, $text)];

            case 'historial':
                $to = strtolower(trim($input['para'] ?? ''));
                if (!$to) {
                    return ['httpCode' => 400, 'body' => ['ok' => false, 'error' => 'Usuario destinatario no especificado']];
                }
                return ['httpCode' => 200, 'body' => $this->chatUseCase->getHistory($currentUser, $to)];

            case 'obtener_usuarios':
                return ['httpCode' => 200, 'body' => $this->chatUseCase->getOnlineUsersWithUnread($currentUser)];

            case 'mensajes_nuevos':
                return ['httpCode' => 200, 'body' => $this->chatUseCase->getNewMessages($currentUser)];

            case 'solicitar_llamada':
                $to = strtolower(trim($input['para'] ?? ''));
                if (!$to) {
                    return ['httpCode' => 400, 'body' => ['ok' => false, 'error' => 'Usuario destino no válido']];
                }
                return ['httpCode' => 200, 'body' => $this->chatUseCase->requestCall($currentUser, $to)];

            default:
                return ['httpCode' => 400, 'body' => ['ok' => false, 'error' => 'Acción no reconocida']];
        }
    }
}
