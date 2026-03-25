<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['uid'])) exit;

$usuario = $_SESSION['usuario'];
$uid     = $_SESSION['uid'];

// 1. Actualizar actividad_usuarios.json
$archivoActividad = __DIR__ . '/actividad_usuarios.json';
$datos = file_exists($archivoActividad)
    ? json_decode(file_get_contents($archivoActividad), true)
    : [];

$datos[$usuario]['ultima_conexion'] = date('Y-m-d H:i:s');
$datos[$usuario]['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

file_put_contents($archivoActividad, json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo '✅';
