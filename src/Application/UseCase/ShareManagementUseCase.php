<?php
namespace Application\UseCase;

use Domain\Repository\ShareRepositoryInterface;
use Domain\Repository\OnlineUsersRepositoryInterface;

class ShareManagementUseCase {
    private ShareRepositoryInterface $shareRepo;
    private OnlineUsersRepositoryInterface $onlineUserRepo;

    public function __construct(ShareRepositoryInterface $shareRepo, OnlineUsersRepositoryInterface $onlineUserRepo) {
        $this->shareRepo = $shareRepo;
        $this->onlineUserRepo = $onlineUserRepo;
    }

    private function getOwnerFolderName(string $username): string {
        $userData = $this->onlineUserRepo->getUserByName($username);
        if ($userData && isset($userData['nombre'])) {
            $nombreCompleto = trim($userData['nombre'] . ' ' . ($userData['apellido'] ?? ''));
            if (!empty($nombreCompleto)) {
                return preg_replace('/[^a-zA-Z0-9_.-]/', '_', str_replace(' ', '_', $nombreCompleto));
            }
        }
        return '';
    }

    public function canManageFolder(string $rutaCarpeta, ?string $currentUser, bool $isAdmin): bool {
        if (!$currentUser || !$rutaCarpeta) return false;
        
        $partesRuta = explode('/', $rutaCarpeta);
        $dueñoCarpeta = $partesRuta[1] ?? null;
        
        $nombreCarpetaUsuarioLogueado = $this->getOwnerFolderName($currentUser);
        
        return $isAdmin || ($nombreCarpetaUsuarioLogueado === $dueñoCarpeta);
    }

    public function getSharingInfo(string $rutaCarpeta, ?string $currentUser, bool $isAdmin): array {
        if (!$this->canManageFolder($rutaCarpeta, $currentUser, $isAdmin)) {
            return ['ok' => false, 'error' => 'No tienes permiso para gestionar esta carpeta.'];
        }

        $compartidoCon = $this->shareRepo->getSharedUsersForPath($rutaCarpeta);
        return ['ok' => true, 'compartidoCon' => $compartidoCon, 'mensaje' => 'Información obtenida.'];
    }

    public function updateSharing(string $rutaCarpeta, array $usuariosSeleccionados, ?string $currentUser, bool $isAdmin): array {
        if (!$this->canManageFolder($rutaCarpeta, $currentUser, $isAdmin)) {
            return ['ok' => false, 'error' => 'No tienes permiso para gestionar esta carpeta.'];
        }

        if (!empty($usuariosSeleccionados)) {
            $this->shareRepo->updateSharedUsersForPath($rutaCarpeta, $usuariosSeleccionados);
        } else {
            $this->shareRepo->removeSharingForPath($rutaCarpeta);
        }

        return ['ok' => true, 'mensaje' => 'Configuración de carpeta compartida actualizada.'];
    }
}
