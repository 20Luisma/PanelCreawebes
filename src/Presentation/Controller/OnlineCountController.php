<?php
namespace Presentation\Controller;

class OnlineCountController {
    private string $activityFilePath;

    public function __construct(string $activityFilePath) {
        $this->activityFilePath = $activityFilePath;
    }

    public function handleRequest(): void {
        header('Content-Type: text/plain; charset=utf-8');
        echo $this->getOnlineCount();
    }

    private function getOnlineCount(): int {
        if (!file_exists($this->activityFilePath)) return 0;

        $datos = json_decode(file_get_contents($this->activityFilePath), true);
        if (!is_array($datos)) return 0;

        $ahora = time();
        $conectados = 0;

        foreach ($datos as $info) {
            if (!isset($info['ultima_conexion'])) continue;
            $ultima = strtotime($info['ultima_conexion']);
            $minutos = ($ahora - $ultima) / 60;
            if ($minutos < 3) {
                $conectados++;
            }
        }

        return $conectados;
    }
}
