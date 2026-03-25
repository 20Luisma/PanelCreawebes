<?php
namespace Domain\Repository;

interface ShareRepositoryInterface {
    public function getSharedUsersForPath(string $path): array;
    public function updateSharedUsersForPath(string $path, array $usernames): void;
    public function removeSharingForPath(string $path): void;
}
