<?php
namespace Application\UseCase;

use Infrastructure\Service\FileSearchService;
use Infrastructure\Service\PathSecurityService;
use Domain\Repository\OnlineUsersRepositoryInterface;

class SearchUseCase {
    private FileSearchService $searchService;
    private PathSecurityService $security;
    private OnlineUsersRepositoryInterface $onlineUserRepo;
    private string $rootDir;

    public function __construct(FileSearchService $searchService, PathSecurityService $security, OnlineUsersRepositoryInterface $onlineUserRepo, string $rootDir) {
        $this->searchService = $searchService;
        $this->security = $security;
        $this->onlineUserRepo = $onlineUserRepo;
        $this->rootDir = $rootDir;
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

    public function performSearch(string $query, string $currentUser, bool $esAdmin, bool $sistemaVisible, bool $overrideActive, ?int $overrideExpiry): array {
        $query = strtolower(trim($query));
        if ($query === '') {
            return [];
        }

        // Todos los usuarios buscan desde la raíz porque el explorador
        // muestra la raíz completa a todos. Los archivos de sistema se
        // filtran según el toggle del admin (sistemaVisible).
        $baseDirAbs = $this->rootDir;

        return $this->searchService->searchDirectory($baseDirAbs, $query, $this->rootDir, $sistemaVisible, $overrideActive, $overrideExpiry);
    }
}
