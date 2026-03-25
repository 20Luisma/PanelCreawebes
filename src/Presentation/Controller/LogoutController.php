<?php
namespace Presentation\Controller;

use Application\UseCase\LogoutUseCase;

class LogoutController {
    private LogoutUseCase $logoutUseCase;

    public function __construct(LogoutUseCase $logoutUseCase) {
        $this->logoutUseCase = $logoutUseCase;
    }

    public function handleRequest(?string $usuario, ?string $uid): void {
        $this->logoutUseCase->execute($usuario, $uid);

        session_unset();
        session_destroy();

        header('Location: login.php?salir=1');
        exit;
    }
}
