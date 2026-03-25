<?php
// Este archivo debe estar en la misma carpeta que chat.php y chat_api.php

function obtenerUsuariosActivos(): array {
    $archivo = __DIR__ . '/actividad_usuarios.json';
    if (!file_exists($archivo)) return [];

    $datos = json_decode(file_get_contents($archivo), true);
    $usuarios = [];
    $ahora = time();

    foreach ($datos as $usuario => $info) {
        if (!isset($info['ultima_conexion'])) continue;

        $ultima = strtotime($info['ultima_conexion']);
        $minutos = ($ahora - $ultima) / 60;

        $estado = $minutos < 3 ? '🟢 Conectado' : ($minutos < 10 ? '🟡 Inactivo' : '🔴 Desconectado');

        $usuarios[$usuario] = [
            'estado' => $estado,
            'ip' => $info['ip'] ?? '',
            'nombre' => $info['nombre'] ?? '',
            'apellido' => $info['apellido'] ?? ''
        ];
    }

    return $usuarios;
}
