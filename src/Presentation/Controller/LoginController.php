<?php
namespace Presentation\Controller;

use Application\UseCase\LoginUseCase;
use Exception;

class LoginController {
    private LoginUseCase $loginUseCase;
    
    // Inyectamos actividad_usuarios manualmente o creamos algo más fino, 
    // pero para mantenerlo al punto, usaremos la vía directa para última_conexion como antes,
    // o mejor delegarlo al UserRepo.
    private string $actividadJsonPath;

    public function __construct(LoginUseCase $loginUseCase, string $actividadJsonPath) {
        $this->loginUseCase = $loginUseCase;
        $this->actividadJsonPath = $actividadJsonPath;
    }

    public function handleRequest(array $postData): ?string {
        if (empty($postData)) {
            return null;
        }

        $usuario = $postData['usuario'] ?? '';
        $clave   = $postData['clave'] ?? '';
        
        // El login resetea la sesión en el viejo code
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $newSessionId = session_id();

        try {
            $user = $this->loginUseCase->execute($usuario, $clave, $newSessionId);
            
            // Éxito:
            $_SESSION['logueado'] = true;
            $_SESSION['usuario']  = $user->getUsername();
            $_SESSION['uid']      = $newSessionId;
            $_SESSION['admin']    = $user->isAdmin();
            
            // Actualizar actividad
            $this->logActivity($user->getUsername());
            
            // Redirigir
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function logActivity(string $username): void {
        $actividad = file_exists($this->actividadJsonPath)
            ? json_decode(file_get_contents($this->actividadJsonPath), true)
            : [];

        $actividad[$username]['ultima_conexion'] = date('Y-m-d H:i:s');
        $actividad[$username]['ip'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        file_put_contents($this->actividadJsonPath, json_encode($actividad, JSON_PRETTY_PRINT));
    }
}
