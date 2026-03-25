<?php
namespace Application\UseCase;

use Domain\Entity\User;
use Domain\Repository\UserRepositoryInterface;
use Domain\Repository\ActiveSessionRepositoryInterface;
use Exception;

class LoginUseCase {
    private UserRepositoryInterface $userRepo;
    private ActiveSessionRepositoryInterface $sessionRepo;

    public function __construct(UserRepositoryInterface $userRepo, ActiveSessionRepositoryInterface $sessionRepo) {
        $this->userRepo = $userRepo;
        $this->sessionRepo = $sessionRepo;
    }

    public function execute(string $username, string $password, string $sessionId): User {
        $user = $this->userRepo->findByUsername($username);
        
        if (!$user) {
            throw new Exception('❌ Usuario o clave incorrecta');
        }

        if (!$user->isActive()) {
            throw new Exception('🚫 Usuario bloqueado.');
        }

        if (!password_verify($password, $user->getPasswordHash())) {
            throw new Exception('❌ Usuario o clave incorrecta');
        }

        // Check concurrent sessions
        $activeSession = $this->sessionRepo->getSessionForUser($username);
        if ($activeSession) {
            $horaGuardada = strtotime($activeSession['hora'] ?? '2000-01-01 00:00:00');
            $haceSegundos = time() - $horaGuardada;
            
            if ($haceSegundos < 180) {
                // To allow transparent re-login in same session, we can skip throwing if strictly needed,
                // But following original logic: we throw unconditionally if < 180s.
                // Wait! If the user refreshes, their session ID might change? No, it's 180 seconds rule.
                throw new Exception('⛔ Este usuario ya tiene una sesión activa. Espere unos minutos o cierre la otra sesión.');
            }
        }

        $now = date('Y-m-d H:i:s');
        
        // Save Active Session
        $this->sessionRepo->saveSession($username, $sessionId, $now);

        return $user;
    }
}
