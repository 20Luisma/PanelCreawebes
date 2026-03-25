<?php
namespace Application\UseCase;

use Domain\Repository\OnlineUsersRepositoryInterface;

class LogoutUseCase {
    private OnlineUsersRepositoryInterface $onlineUserRepo;
    private string $onlineFilePath;
    private string $activityFilePath;
    private string $activeSessionsFilePath;

    public function __construct(
        string $onlineFilePath,
        string $activityFilePath,
        string $activeSessionsFilePath
    ) {
        $this->onlineFilePath = $onlineFilePath;
        $this->activityFilePath = $activityFilePath;
        $this->activeSessionsFilePath = $activeSessionsFilePath;
    }

    public function execute(?string $usuario, ?string $uid): void {
        if (!$usuario) return;

        $this->removeFromOnlineUsers($usuario);
        $this->recordDisconnection($usuario);
        $this->removeActiveSession($usuario, $uid);
    }

    private function removeFromOnlineUsers(string $usuario): void {
        if (!file_exists($this->onlineFilePath)) return;
        $data = json_decode(file_get_contents($this->onlineFilePath), true) ?? [];
        unset($data[$usuario]);
        file_put_contents($this->onlineFilePath, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
    }

    private function recordDisconnection(string $usuario): void {
        if (!file_exists($this->activityFilePath)) return;
        $data = json_decode(file_get_contents($this->activityFilePath), true) ?? [];
        if (!isset($data[$usuario])) {
            $data[$usuario] = [];
        }
        $data[$usuario]['ultima_desconexion'] = date('Y-m-d H:i:s');
        file_put_contents($this->activityFilePath, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
    }

    private function removeActiveSession(string $usuario, ?string $uid): void {
        if (!$uid || !file_exists($this->activeSessionsFilePath)) return;
        $data = json_decode(file_get_contents($this->activeSessionsFilePath), true) ?? [];
        if (isset($data[$usuario]) && $data[$usuario]['session_id'] === $uid) {
            unset($data[$usuario]);
            file_put_contents($this->activeSessionsFilePath, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
        }
    }
}
