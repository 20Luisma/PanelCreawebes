<?php
// Archivo: api_contador.php

// Incluimos las funciones necesarias
require_once __DIR__ . '/funciones.php';

// Obtenemos todos los usuarios y su estado
$usuarios = obtenerUsuariosActivos();

// Filtramos para quedarnos solo con los que están "Conectado"
$conectados = array_filter($usuarios, function($usuario) {
    return $usuario['estado'] === '🟢 Conectado';
});

// Imprimimos la cantidad. Esto es lo que recibe el JavaScript.
echo count($conectados);