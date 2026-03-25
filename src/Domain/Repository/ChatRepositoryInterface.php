<?php
namespace Domain\Repository;

interface ChatRepositoryInterface {
    public function getAllMessages(): array;
    public function saveMessages(array $messages): bool;
    public function addMessage(array $message): bool;
}
