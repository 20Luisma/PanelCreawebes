<?php

spl_autoload_register(function ($class) {
    // La estructura de namespace coincide con la de carpetas: Domain\..., Application\...
    $base_dir = __DIR__ . '/';
    
    // Reemplaza barras invertidas por barras diagonales.
    $file = $base_dir . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});
