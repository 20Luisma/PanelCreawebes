<?php
// Nos aseguramos de que la sesión esté iniciada, pero no la iniciamos si ya lo está.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define la carpeta raíz del proyecto de forma canónica.
// Es vital que este ROOT_DIR sea el mismo que se usa en index.php
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', realpath(__DIR__));
}

// Cargar variables de entorno estáticas desde el .env
$envFile = ROOT_DIR . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', rtrim($line), 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

require_once ROOT_DIR . '/src/autoload.php';

use Application\UseCase\SessionVerificationUseCase;
use Infrastructure\Persistence\FileActiveSessionRepository;
use Infrastructure\Service\PathSecurityService;

$maxTiempoInactivo = 900; // 15 minutos
$usuario = $_SESSION['usuario'] ?? null;
$uid     = $_SESSION['uid'] ?? null;
$esAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] === true; // Aseguramos que esAdmin esté disponible

// Primero, verificamos si está logueado. Si no, lo mandamos fuera.
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    // Si la cabecera ya fue enviada (estamos en un script AJAX), no redirigimos.
    if (!headers_sent()) {
        header('Location: login.php');
    }
    exit;
}

// Inicializamos el Repositorio y el Caso de Uso para la verificación de sesión
$sessionRepo = new FileActiveSessionRepository(__DIR__ . '/usuarios_activos.json');
$sessionUseCase = new SessionVerificationUseCase($sessionRepo);

$ultimoMovimiento = $_SESSION['ultimo_movimiento'] ?? null;
$esPolling = defined('NO_ACTUALIZAR_INACTIVIDAD') && NO_ACTUALIZAR_INACTIVIDAD === true;

// Verificamos inactividad y actualizamos la base de datos local
$sessionValida = $sessionUseCase->verifyAndRefreshSession(
    $maxTiempoInactivo,
    $usuario,
    $uid,
    $ultimoMovimiento,
    $esPolling
);

if (!$sessionValida) {
    session_unset();
    session_destroy();

    if (!headers_sent()) {
        header('Location: login.php?error=inactividad');
    }
    exit;
}

// Actualizamos el movimiento en la sesión actual de PHP solo si NO es un polling API
if (!$esPolling) {
    $_SESSION['ultimo_movimiento'] = time();
}


// =========================================================
// ✅ INICIO: INSTANCIA CENTRALIZADA PARA PROTECCIÓN DE RUTAS
// =========================================================
$pathSecurityService = new PathSecurityService(ROOT_DIR);

// Mantenemos alias globales para compatibilidad hacia atrás temporal
if (!function_exists('normalizaRuta')) {
    function normalizaRuta($p) {
        global $pathSecurityService;
        return $pathSecurityService->normalizaRuta($p);
    }
}

if (!function_exists('estaDentroDe')) {
    function estaDentroDe($hijo, $padre) {
        global $pathSecurityService;
        return $pathSecurityService->estaDentroDe($hijo, $padre);
    }
}

if (!function_exists('vaAQuedarComoRootIndex')) {
    function vaAQuedarComoRootIndex($destDirAbs, $nombre) {
        global $pathSecurityService;
        return $pathSecurityService->vaAQuedarComoRootIndex($destDirAbs, $nombre);
    }
}

if (!function_exists('esRootIndex')) {
    function esRootIndex($absPath) {
        global $pathSecurityService;
        return $pathSecurityService->esRootIndex($absPath);
    }
}

if (!function_exists('puedeTocarRootIndex')) {
    function puedeTocarRootIndex() {
        global $esAdmin, $pathSecurityService;
        return $pathSecurityService->puedeTocarRootIndex(
            $esAdmin,
            $_SESSION['override_root_index_active'] ?? false,
            $_SESSION['override_root_index_expiry'] ?? null
        );
    }
}

if (!function_exists('habilitarOverrideRootIndex')) {
    function habilitarOverrideRootIndex($minutos) {
        global $esAdmin;
        if ($esAdmin) {
            $_SESSION['override_root_index_active'] = true;
            $_SESSION['override_root_index_expiry'] = time() + ($minutos * 60);
        }
    }
}

if (!function_exists('deshabilitarOverrideRootIndex')) {
    function deshabilitarOverrideRootIndex() {
        unset($_SESSION['override_root_index_active']);
        unset($_SESSION['override_root_index_expiry']);
    }
}

?>