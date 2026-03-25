<?php
namespace Domain\Repository;

interface ActiveSessionRepositoryInterface {
    public function getSessionForUser(string $username): ?array;
    public function saveSession(string $username, string $sessionId, string $dateTime): void;
    public function deleteSession(string $username): void;
}
