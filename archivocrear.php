<?php
session_start();

$root = realpath(__DIR__);
$mensaje = '';
$reemplazar = isset($_POST['forzar']) && $_POST['forzar'] === '1';

// Detectar carpeta actual desde URL
$carpetaSeleccionada = $_GET['carpeta'] ?? '';

function obtenerCarpetas($base, $root, $nivel = 0) {
    $lista = [];
    $items = scandir($base);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $ruta = $base . '/' . $item;
        if (is_dir($ruta)) {
            $rel = ltrim(str_replace($root, '', $ruta), '/\\');
            $indent = str_repeat('— ', $nivel);
            $lista[] = ['ruta' => $rel, 'nombre' => $indent . ($rel ?: '/')];
            $lista = array_merge($lista, obtenerCarpetas($ruta, $root, $nivel + 1));
        }
    }
    return $lista;
}
$carpetas = obtenerCarpetas($root, $root);

function obtenerRutaRelativa($root, $abs) {
    return ltrim(str_replace($root, '', $abs), '/\\');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreArchivo = trim($_POST['nombre'] ?? '');
    $extManual     = trim($_POST['extension'] ?? '');
    $carpetaRel    = trim($_POST['carpeta'] ?? '');
    $codigoBase64  = $_POST['codigo'] ?? '';

    if (!$nombreArchivo) {
        $mensaje = '❌ Debes indicar un nombre de archivo.';
    } elseif (!$extManual) {
        $mensaje = '❌ Debes indicar una extensión.';
    } else {
        if (!str_ends_with($nombreArchivo, ".$extManual")) {
            $nombreArchivo .= ".$extManual";
        }

        $rutaDestinoAbs = realpath($root . '/' . $carpetaRel);
        if (!$rutaDestinoAbs || strpos($rutaDestinoAbs, $root) !== 0) {
            $mensaje = '❌ Carpeta no permitida.';
        } else {
            $rutaFinal = $rutaDestinoAbs . '/' . $nombreArchivo;
            if (file_exists($rutaFinal) && !$reemplazar) {
                $mensaje = '⚠️ El archivo ya existe: ' . obtenerRutaRelativa($root, $rutaFinal) .
                           '<br><br><form method="post" onsubmit="beforeSubmit()">'
                           . '<input type="hidden" name="nombre" value="' . htmlspecialchars($_POST['nombre']) . '">'
                           . '<input type="hidden" name="extension" value="' . htmlspecialchars($_POST['extension']) . '">'
                           . '<input type="hidden" name="carpeta" value="' . htmlspecialchars($_POST['carpeta']) . '">'
                           . '<input type="hidden" name="codigo" value="' . htmlspecialchars($_POST['codigo']) . '">'
                           . '<input type="hidden" name="forzar" value="1">'
                           . '<button type="submit">✅ Sí, reemplazar</button></form>';
            } else {
                if (!is_dir(dirname($rutaFinal))) {
                    mkdir(dirname($rutaFinal), 0777, true);
                }

                $codigo = base64_decode($codigoBase64);
                $ok = @file_put_contents($rutaFinal, $codigo);
                if ($ok === false) {
                    $mensaje = '❌ No se pudo guardar. Revisá permisos.';
                } else {
                    $mensaje = '✅ Archivo guardado en <strong>' .
                               obtenerRutaRelativa($root, $rutaFinal) . '</strong>';
                }
            }
        }
    }
}
?>
