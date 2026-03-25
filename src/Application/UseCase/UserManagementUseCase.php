<?php
namespace Application\UseCase;

use Domain\Entity\User;
use Domain\Repository\UserRepositoryInterface;
use Domain\Service\MailerInterface;
use Exception;

class UserManagementUseCase {
    private UserRepositoryInterface $userRepository;
    private MailerInterface $mailer;
    private string $usersBaseDir;

    public function __construct(
        UserRepositoryInterface $userRepository,
        MailerInterface $mailer,
        string $usersBaseDir
    ) {
        $this->userRepository = $userRepository;
        $this->mailer = $mailer;
        $this->usersBaseDir = $usersBaseDir;
    }

    public function createManualUser(string $username, string $password, string $email, string $firstName, string $lastName): string {
        if (empty($username) || empty($password) || empty($email) || empty($firstName) || empty($lastName)) {
            throw new Exception('❌ Todos los campos son obligatorios.');
        }

        if ($this->userRepository->findByUsername($username)) {
            throw new Exception('⚠️ Ese nombre de usuario ya existe.');
        }

        $user = new User(
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            true, // isActive
            false, // isAdmin
            $email,
            $firstName,
            $lastName,
            $_SERVER['REMOTE_ADDR'] ?? null,
            'Agregado',
            date('Y-m-d H:i:s')
        );

        $this->createUserFolder($firstName, $lastName);
        $this->userRepository->save($user);
        $this->mailer->sendCredentials($username, $password, $email, $firstName, $lastName);

        return '✅ Usuario creado y credenciales enviadas.';
    }

    public function createAutoUser(string $email, string $firstName, string $lastName): string {
        if (empty($email) || empty($firstName) || empty($lastName)) {
            throw new Exception('❌ Nombre, apellido y email son obligatorios.');
        }

        do {
            $username = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 5);
        } while ($this->userRepository->findByUsername($username));

        $password = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@$%&*?'), 0, 10);

        $user = new User(
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            true,
            false,
            $email,
            $firstName,
            $lastName,
            $_SERVER['REMOTE_ADDR'] ?? null,
            'Agregado',
            date('Y-m-d H:i:s')
        );

        $this->createUserFolder($firstName, $lastName);
        $this->userRepository->save($user);
        $this->mailer->sendCredentials($username, $password, $email, $firstName, $lastName);

        return "✅ Usuario generado y enviado. Usuario: $username";
    }

    public function deleteUser(string $username): string {
        if ($username === 'Luisma') {
            throw new Exception('❌ No se puede eliminar al superadministrador.');
        }

        $this->userRepository->delete($username);
        return '🗑️ Usuario eliminado correctamente.';
    }

    public function blockUser(string $username): string {
        if ($username === 'Luisma') {
            throw new Exception('❌ No se puede bloquear al superadministrador.');
        }

        $user = $this->userRepository->findByUsername($username);
        if ($user) {
            $user->setActive(false);
            $this->userRepository->save($user);
            return '🚫 Usuario bloqueado.';
        }
        throw new Exception('❌ Usuario no encontrado.');
    }

    public function activateUser(string $username): string {
        $user = $this->userRepository->findByUsername($username);
        if ($user) {
            $user->setActive(true);
            $this->userRepository->save($user);
            return '✅ Usuario activado.';
        }
        throw new Exception('❌ Usuario no encontrado.');
    }

    public function toggleAdmin(string $username, bool $isAdmin): string {
        if ($username === 'Luisma') {
            throw new Exception('❌ No se puede cambiar los permisos del superadministrador.');
        }

        $user = $this->userRepository->findByUsername($username);
        if ($user) {
            $user->setAdmin($isAdmin);
            $this->userRepository->save($user);
            $estado = $isAdmin ? 'concedidos' : 'revocados';
            return "✅ Permisos de administrador {$estado} para el usuario " . htmlspecialchars($username) . ".";
        }
        throw new Exception('❌ Usuario no encontrado.');
    }

    private function createUserFolder(string $firstName, string $lastName): void {
        $nombreCompleto = trim($firstName . ' ' . $lastName);
        $nombreSeguro = preg_replace('/[^a-zA-Z0-9_.-]/', '', str_replace(' ', '_', $nombreCompleto));
        
        if (!is_dir($this->usersBaseDir)) {
            mkdir($this->usersBaseDir, 0755, true);
        }
        
        $rutaCarpeta = $this->usersBaseDir . '/' . $nombreSeguro;
        if (!empty($nombreSeguro) && !file_exists($rutaCarpeta)) {
            mkdir($rutaCarpeta, 0755, true);
        }
    }
}
