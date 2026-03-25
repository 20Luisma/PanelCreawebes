<?php
namespace Infrastructure\Persistence;

use Domain\Entity\User;
use Domain\Repository\UserRepositoryInterface;

class FileUserRepository implements UserRepositoryInterface {
    private string $usersFilePath;
    private string $activityFilePath;

    public function __construct(string $usersFilePath, string $activityFilePath) {
        $this->usersFilePath = $usersFilePath;
        $this->activityFilePath = $activityFilePath;
    }

    private function loadUsersArray(): array {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($this->usersFilePath, true);
        }
        return file_exists($this->usersFilePath) ? require $this->usersFilePath : [];
    }

    private function loadActivityArray(): array {
        return file_exists($this->activityFilePath) ? json_decode(file_get_contents($this->activityFilePath), true) ?: [] : [];
    }

    public function findAll(): array {
        $usersData = $this->loadUsersArray();
        $activityData = $this->loadActivityArray();
        
        $users = [];
        foreach ($usersData as $username => $data) {
            $act = $activityData[$username] ?? [];
            $users[] = new User(
                $username,
                $data['clave'] ?? '',
                $data['activo'] ?? true,
                $data['admin'] ?? false,
                $act['email'] ?? null,
                $act['nombre'] ?? null,
                $act['apellido'] ?? null,
                $act['ip'] ?? null,
                $act['accion'] ?? null,
                $act['fecha'] ?? null
            );
        }
        return $users;
    }

    public function findByUsername(string $username): ?User {
        $usersData = $this->loadUsersArray();
        if (!isset($usersData[$username])) {
            return null;
        }
        
        $activityData = $this->loadActivityArray();
        $data = $usersData[$username];
        $act = $activityData[$username] ?? [];
        
        return new User(
            $username,
            $data['clave'] ?? '',
            $data['activo'] ?? true,
            $data['admin'] ?? false,
            $act['email'] ?? null,
            $act['nombre'] ?? null,
            $act['apellido'] ?? null,
            $act['ip'] ?? null,
            $act['accion'] ?? null,
            $act['fecha'] ?? null
        );
    }

    public function save(User $user): void {
        $usersData = $this->loadUsersArray();
        $activityData = $this->loadActivityArray();
        
        $username = $user->getUsername();
        
        $usersData[$username] = [
            'clave' => $user->getPasswordHash(),
            'activo' => $user->isActive(),
            'admin' => $user->isAdmin()
        ];
        
        // Preserve existing activity or update
        $act = $activityData[$username] ?? [];
        if ($user->getEmail()) $act['email'] = $user->getEmail();
        if ($user->getFirstName()) $act['nombre'] = $user->getFirstName();
        if ($user->getLastName()) $act['apellido'] = $user->getLastName();
        if ($user->getIp()) $act['ip'] = $user->getIp();
        if ($user->getLastAction()) $act['accion'] = $user->getLastAction();
        if ($user->getLastActionDate()) $act['fecha'] = $user->getLastActionDate();
        
        $activityData[$username] = $act;
        
        $this->saveToDisk($usersData, $activityData);
    }

    public function delete(string $username): void {
        $usersData = $this->loadUsersArray();
        $activityData = $this->loadActivityArray();
        
        if (isset($usersData[$username])) {
            unset($usersData[$username]);
        }
        if (isset($activityData[$username])) {
            unset($activityData[$username]);
        }
        
        $this->saveToDisk($usersData, $activityData);
    }

    private function saveToDisk(array $usersData, array $activityData): void {
        $contenido = "<?php\nreturn [\n";
        foreach ($usersData as $user => $data) {
            $clave_escapada = addslashes($data['clave']);
            $activo_str = $data['activo'] ? 'true' : 'false';
            $admin_str = ($data['admin'] ?? false) ? 'true' : 'false'; 
            $contenido .= "    '" . addslashes($user) . "' => ['clave' => '{$clave_escapada}', 'activo' => {$activo_str}, 'admin' => {$admin_str}],\n";
        }
        $contenido .= "];\n";

        file_put_contents($this->usersFilePath, $contenido);
        file_put_contents($this->activityFilePath, json_encode($activityData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($this->usersFilePath, true);
        }
    }
}
