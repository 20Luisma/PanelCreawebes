<?php
namespace Infrastructure\Persistence;

use Domain\Repository\ShareRepositoryInterface;

class FileShareRepository implements ShareRepositoryInterface {
    private string $filePath;

    public function __construct(string $filePath) {
        $this->filePath = $filePath;
    }

    private function loadData(): array {
        return file_exists($this->filePath) ? json_decode(file_get_contents($this->filePath), true) ?: [] : [];
    }

    private function saveData(array $data): void {
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function getSharedUsersForPath(string $path): array {
        $data = $this->loadData();
        return $data[$path] ?? [];
    }

    public function updateSharedUsersForPath(string $path, array $usernames): void {
        $data = $this->loadData();
        $data[$path] = array_values($usernames);
        $this->saveData($data);
    }

    public function removeSharingForPath(string $path): void {
        $data = $this->loadData();
        if (isset($data[$path])) {
            unset($data[$path]);
            $this->saveData($data);
        }
    }
}
