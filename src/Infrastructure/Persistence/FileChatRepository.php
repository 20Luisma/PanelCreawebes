<?php
namespace Infrastructure\Persistence;

use Domain\Repository\ChatRepositoryInterface;

class FileChatRepository implements ChatRepositoryInterface {
    private string $filePath;

    public function __construct(string $filePath) {
        $this->filePath = $filePath;
    }

    public function getAllMessages(): array {
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, '[]');
            return [];
        }
        $contenido = file_get_contents($this->filePath);
        return $contenido ? (json_decode($contenido, true) ?? []) : [];
    }

    public function saveMessages(array $messages): bool {
        return file_put_contents(
            $this->filePath,
            json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ) !== false;
    }

    public function addMessage(array $message): bool {
        $messages = $this->getAllMessages();
        $messages[] = $message;
        return $this->saveMessages($messages);
    }
}
