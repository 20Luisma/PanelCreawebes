<?php
namespace Presentation\Controller;

use Infrastructure\Service\BackupService;

class BackupController {
    private BackupService $backupService;

    public function __construct(BackupService $backupService) {
        $this->backupService = $backupService;
    }

    public function handleRequest(array $postData): void {
        header('Content-Type: application/json; charset=utf-8');

        ignore_user_abort(true);
        set_time_limit(0);

        $accion = $postData['accion'] ?? '';

        $result = match ($accion) {
            'init'   => $this->backupService->init(),
            'status' => $this->backupService->status(),
            'abort'  => $this->backupService->abort(),
            'chunk'  => $this->backupService->chunk(
                isset($postData['batch']) ? max(1, (int)$postData['batch']) : 300
            ),
            default  => ['error' => 'Acción inválida'],
        };

        echo json_encode($result);
    }
}
