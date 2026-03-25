<?php
namespace Infrastructure\Persistence;

use Domain\Repository\OnlineUsersRepositoryInterface;

class FileOnlineUsersRepository implements OnlineUsersRepositoryInterface {
    private string $activityFilePath;

    public function __construct(string $activityFilePath) {
        $this->activityFilePath = $activityFilePath;
    }

    public function getActiveUsers(): array {
        if (!file_exists($this->activityFilePath)) return [];

        $datos = json_decode(file_get_contents($this->activityFilePath), true);
        if (!is_array($datos)) return [];

        $usuarios = [];
        $ahora = time();

        foreach ($datos as $usuario => $info) {
            if (!isset($info['ultima_conexion'])) continue;

            $ultima  = strtotime($info['ultima_conexion']);
            $minutos = ($ahora - $ultima) / 60;

            $estado = $minutos < 3 ? '🟢 Conectado' : ($minutos < 10 ? '🟡 Inactivo' : '🔴 Desconectado');

            $usuarios[$usuario] = [
                'estado'   => $estado,
                'ip'       => $info['ip'] ?? '',
                'nombre'   => $info['nombre'] ?? '',
                'apellido' => $info['apellido'] ?? '',
            ];
        }

        return $usuarios;
    }

    public function getUserByName(string $username): ?array {
        if (!file_exists($this->activityFilePath)) return null;
        $datos = json_decode(file_get_contents($this->activityFilePath), true);
        return $datos[$username] ?? null;
    }
}
