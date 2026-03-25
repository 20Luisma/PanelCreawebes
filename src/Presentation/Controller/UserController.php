<?php
namespace Presentation\Controller;

use Application\UseCase\UserManagementUseCase;
use Domain\Repository\UserRepositoryInterface;
use Exception;

class UserController {
    private UserManagementUseCase $useCase;
    private UserRepositoryInterface $repository;

    public function __construct(UserManagementUseCase $useCase, UserRepositoryInterface $repository) {
        $this->useCase = $useCase;
        $this->repository = $repository;
    }

    public function handleRequest(array $postData): ?string {
        if (empty($postData) || !isset($postData['accion'])) {
            return null;
        }

        $accion = $postData['accion'] ?? '';
        $usuario = $postData['usuario'] ?? '';
        $mensaje = null;

        try {
            switch ($accion) {
                case 'crear_manual':
                    if (($postData['clave1'] ?? '') !== ($postData['clave2'] ?? '')) {
                        throw new Exception('⚠️ Las contraseñas no coinciden.');
                    }
                    $mensaje = $this->useCase->createManualUser(
                        trim($postData['usuario'] ?? ''),
                        $postData['clave1'] ?? '',
                        trim($postData['email'] ?? ''),
                        trim($postData['nombre'] ?? ''),
                        trim($postData['apellido'] ?? '')
                    );
                    break;

                case 'crear_auto':
                    $mensaje = $this->useCase->createAutoUser(
                        trim($postData['email_auto'] ?? ''),
                        trim($postData['nombre_auto'] ?? ''),
                        trim($postData['apellido_auto'] ?? '')
                    );
                    break;

                case 'eliminar':
                    if ($usuario) $mensaje = $this->useCase->deleteUser($usuario);
                    break;

                case 'bloquear':
                    if ($usuario) $mensaje = $this->useCase->blockUser($usuario);
                    break;

                case 'activar':
                    if ($usuario) $mensaje = $this->useCase->activateUser($usuario);
                    break;

                case 'toggle_admin':
                    if ($usuario) {
                        $isAdmin = isset($postData['es_admin']);
                        $mensaje = $this->useCase->toggleAdmin($usuario, $isAdmin);
                    }
                    break;
            }
        } catch (Exception $e) {
            $mensaje = $e->getMessage();
        }

        return $mensaje;
    }

    public function getAllUsers(): array {
        return $this->repository->findAll();
    }
}
