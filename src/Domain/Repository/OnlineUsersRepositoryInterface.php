<?php
namespace Domain\Repository;

interface OnlineUsersRepositoryInterface {
    /**
     * Devuelve un array asociativo [username => ['estado'=>..., 'ip'=>..., 'nombre'=>..., 'apellido'=>...]]
     */
    public function getActiveUsers(): array;
    public function getUserByName(string $username): ?array;
}
