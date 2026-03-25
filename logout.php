<?php
session_start();

$usuario = $_SESSION['usuario'] ?? null;
$uid     = $_SESSION['uid'] ?? null;

if ($usuario) {
    // --- 1. Eliminar del archivo .usuarios_online.json ---
    $archivoOnline = __DIR__ . '/.usuarios_online.json';
    if (file_exists($archivoOnline)) {
        $usuariosOnline = json_decode(file_get_contents($archivoOnline), true);
        unset($usuariosOnline[$usuario]);
        file_put_contents($archivoOnline, json_encode($usuariosOnline, JSON_PRETTY_PRINT));
    }

    // --- 2. Registrar hora de desconexión en actividad_usuarios.json ---
    $archivoActividad = __DIR__ . '/actividad_usuarios.json';
    if (file_exists($archivoActividad)) {
        $actividad = json_decode(file_get_contents($archivoActividad), true);
        if (!isset($actividad[$usuario])) {
            $actividad[$usuario] = [];
        }
        $actividad[$usuario]['ultima_desconexion'] = date('Y-m-d H:i:s');
        file_put_contents($archivoActividad, json_encode($actividad, JSON_PRETTY_PRINT));
    }

    // --- 3. Eliminar de usuarios_activos.json si coincide el UID ---
    $archivoSesiones = __DIR__ . '/usuarios_activos.json';
    if ($uid && file_exists($archivoSesiones)) {
        $sesiones = json_decode(file_get_contents($archivoSesiones), true);
        if (isset($sesiones[$usuario]) && $sesiones[$usuario]['session_id'] === $uid) {
            unset($sesiones[$usuario]);
            file_put_contents($archivoSesiones, json_encode($sesiones, JSON_PRETTY_PRINT));
        }
    }
}

// Cerrar sesión
session_unset();
session_destroy();

// Redirigir al login
header('Location: login.php?salir=1');
exit;
