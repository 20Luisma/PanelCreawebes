<?php
namespace Infrastructure\Persistence;

use Domain\Repository\ActiveSessionRepositoryInterface;

class FileActiveSessionRepository implements ActiveSessionRepositoryInterface {
    private string $filePath;

    public function __construct(string $filePath) {
        $this->filePath = $filePath;
    }

    private function loadData(): array {
        return file_exists($this->filePath) ? json_decode(file_get_contents($this->filePath), true) ?: [] : [];
    }

    public function getSessionForUser(string $username): ?array {
        $data = $this->loadData();
        return $data[$username] ?? null;
    }

    public function saveSession(string $username, string $sessionId, string $dateTime): void {
        $data = $this->loadData();
        $data[$username] = [
            'session_id' => $sessionId,
            'hora'       => $dateTime
        ];
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function deleteSession(string $username): void {
        $data = $this->loadData();
        if (isset($data[$username])) {
            unset($data[$username]);
            file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
        }
    }
}
