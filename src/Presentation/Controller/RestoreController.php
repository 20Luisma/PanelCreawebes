<?php
namespace Presentation\Controller;

use Infrastructure\Service\RestoreService;

class RestoreController {
    private RestoreService $restoreService;
    private string $rootDir;

    public function __construct(RestoreService $restoreService, string $rootDir) {
        $this->restoreService = $restoreService;
        $this->rootDir = $rootDir;
    }

    public function handleRequest(array $postData, string $csrfSession, bool $allowIndexOverride): void {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        ignore_user_abort(true);
        set_time_limit(0);

        $accion  = $postData['accion'] ?? '';
        $zipName = basename($postData['zip'] ?? '');
        $zipAbs  = realpath($this->restoreService->getBackupDir() . '/' . $zipName);

        // Validar CSRF
        $csrfOk = isset($postData['csrf']) && hash_equals($csrfSession, $postData['csrf']);
        if (!$csrfOk) { echo json_encode(['error' => 'CSRF inválido']); return; }
        if (!$zipAbs || !is_file($zipAbs)) { echo json_encode(['error' => 'ZIP inválido']); return; }

        // Estado por sesión + zip
        $stateKey  = 'restore_quick_' . session_id() . '_' . sha1($zipAbs);
        $tmpDir    = $this->rootDir . '/_backups/tmp';
        $stateFile = $tmpDir . "/{$stateKey}.json";

        // Rutas a excluir siempre (auto-pisado)
        $excludeExact = [];
        $rootNorm = rtrim(str_replace('\\', '/', $this->rootDir), '/');
        $selfPath = str_replace('\\', '/', realpath(__DIR__ . '/../../restaurar_proc.php') ?: '');
        $uiPath   = str_replace('\\', '/', realpath(__DIR__ . '/../../restaurar.php') ?: '');

        if (str_starts_with($selfPath, $rootNorm . '/')) {
            $excludeExact[] = substr($selfPath, strlen($rootNorm) + 1);
        }
        if (str_starts_with($uiPath, $rootNorm . '/')) {
            $excludeExact[] = substr($uiPath, strlen($rootNorm) + 1);
        }

        $result = match ($accion) {
            'init'  => $this->restoreService->init($zipAbs, $allowIndexOverride, $stateFile, $excludeExact),
            'chunk' => $this->restoreService->chunk($stateFile),
            default => ['error' => 'Acción inválida'],
        };

        echo json_encode($result);
    }

    public function isFinalized(array $result): bool {
        return !empty($result['finalizado']);
    }
}
