<?php
namespace Presentation\Controller;

use Application\UseCase\ShareManagementUseCase;

class ShareController {
    private ShareManagementUseCase $shareUseCase;

    public function __construct(ShareManagementUseCase $shareUseCase) {
        $this->shareUseCase = $shareUseCase;
    }

    public function handleRequest(array $postData, ?string $currentUser, bool $isAdmin): void {
        header('Content-Type: application/json');

        $accion = $postData['accion'] ?? '';
        $rutaCarpeta = $postData['ruta'] ?? '';

        $respuesta = ['ok' => false, 'mensaje' => 'Acción no válida.'];

        if ($currentUser && $rutaCarpeta) {
            switch ($accion) {
                case 'get_info':
                    $respuesta = $this->shareUseCase->getSharingInfo($rutaCarpeta, $currentUser, $isAdmin);
                    break;

                case 'update_sharing':
                    $usuariosSeleccionados = $postData['usuarios'] ?? [];
                    $respuesta = $this->shareUseCase->updateSharing($rutaCarpeta, $usuariosSeleccionados, $currentUser, $isAdmin);
                    break;
            }
        }

        echo json_encode($respuesta);
    }
}
