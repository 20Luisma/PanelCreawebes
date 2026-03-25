<?php
namespace Application\UseCase;

use Domain\Repository\ActiveSessionRepositoryInterface;

class SessionVerificationUseCase {
    private ActiveSessionRepositoryInterface $sessionRepo;

    public function __construct(ActiveSessionRepositoryInterface $sessionRepo) {
        $this->sessionRepo = $sessionRepo;
    }

    /**
     * @return bool True si la sesión es válida, False si caducó o es inválida
     */
    public function verifyAndRefreshSession(
        int $maxTiempoInactivo,
        ?string $usuario,
        ?string $uid,
        ?int $ultimoMovimiento,
        bool $esPolling = false
    ): bool {
        // Verificar inactividad
        if ($ultimoMovimiento !== null) {
            $inactivo = time() - $ultimoMovimiento;
            if ($inactivo > $maxTiempoInactivo) {
                if ($usuario && $uid) {
                    $sessionData = $this->sessionRepo->getSessionForUser($usuario);
                    if ($sessionData && isset($sessionData['session_id']) && $sessionData['session_id'] === $uid) {
                        $this->sessionRepo->deleteSession($usuario);
                    }
                }
                return false;
            }
        }

        // Si todo está bien, actualizamos la hora en la DB JSON (solo si no es un polling background)
        if (!$esPolling && $usuario && $uid) {
            $sessionData = $this->sessionRepo->getSessionForUser($usuario);
            if ($sessionData && isset($sessionData['session_id']) && $sessionData['session_id'] === $uid) {
                $this->sessionRepo->saveSession($usuario, $uid, date('Y-m-d H:i:s'));
            }
        }

        return true;
    }
}
