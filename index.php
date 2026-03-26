<?php
// 1. Incluimos nuestro gestor de sesión.
//    Él se encarga de TODO: iniciar la sesión, verificar la inactividad y actualizarla.
require_once __DIR__ . '/verificar_sesion.php'; // ROOT_DIR ya está definido aquí y las funciones de protección

// 2. Cerramos la sesión para escritura.
//    Esto es VITAL para que los clics rápidos no te deslogueen.
//    Este paso solo se hace en `index.php` (la interfaz principal).
session_write_close();
header('Content-Type: text/html; charset=UTF-8');

// 3. Ya podemos usar las variables de sesión de forma segura.
//    La variable sigue existiendo aunque la sesión esté "cerrada" para escritura.
// $esAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] === true; // Ya definida en verificar_sesion.php y globalmente disponible

// ### INICIO: NUEVO CÓDIGO PARA GESTIONAR CARPETAS DE USUARIO ###
$archivoActividad = __DIR__ . '/actividad_usuarios.json';
$actividadUsuarios = file_exists($archivoActividad) ? json_decode(file_get_contents($archivoActividad), true) : [];
$nombreCarpetaUsuarioLogueado = '';
$carpetasDeUsuarios = [];

// Generar lista de nombres de carpeta para todos los usuarios
if (is_array($actividadUsuarios)) {
    foreach ($actividadUsuarios as $datos) {
        if (isset($datos['nombre']) && isset($datos['apellido'])) {
            $nombreCompleto = trim($datos['nombre'] . ' ' . $datos['apellido']);
            if (!empty($nombreCompleto)) {
                // Genera un nombre de carpeta seguro (debe coincidir con gestor_usuarios.php)
                $nombreSanitizado = str_replace(' ', '_', $nombreCompleto);
$nombreSanitizado = preg_replace('/[^\p{L}0-9_.-]/u', '_', $nombreSanitizado);
$carpetasDeUsuarios[] = $nombreSanitizado;

            }
        }
    }
}

// Obtener el nombre de la carpeta para el usuario actual
if (isset($_SESSION['usuario']) && isset($actividadUsuarios[$_SESSION['usuario']])) {
    $datosUsuario = $actividadUsuarios[$_SESSION['usuario']];
    $nombreCompleto = trim(($datosUsuario['nombre'] ?? '') . ' ' . ($datosUsuario['apellido'] ?? ''));
    if (!empty($nombreCompleto)) {
        $nombreCarpetaUsuarioLogueado = preg_replace('/[^a-zA-Z0-9_.-]/', '_', str_replace(' ', '_', $nombreCompleto));
    }
}
// ### FIN: NUEVO CÓDIGO ###

// ### INICIO: CÓDIGO PARA CARPETAS COMPARTIDAS ###
$archivoCompartidos = __DIR__ . '/compartidos.json';
$infoCompartidos = file_exists($archivoCompartidos) ? json_decode(file_get_contents($archivoCompartidos), true) : [];

// Creamos un mapa de carpetas compartidas CONMIGO para un acceso rápido
$compartidasConmigo = [];
if (!$esAdmin && !empty($nombreCarpetaUsuarioLogueado)) {
    foreach ($infoCompartidos as $rutaPadre => $usuariosConAcceso) {
        if (in_array($nombreCarpetaUsuarioLogueado, $usuariosConAcceso)) {
            // El usuario logueado tiene acceso a $rutaPadre
            // Si estamos viendo la carpeta "usuarios", marcaremos la carpeta del dueño
            $partes = explode('/', $rutaPadre);
            if (count($partes) > 1) {
                 $compartidasConmigo[] = $partes[1];
            }
        }
    }
}
// ### FIN: CÓDIGO PARA CARPETAS COMPARTIDAS ###


/* =========================================================
 * Explorador de Archivos – Creawebes (versión 14-jun-2025)
 * Ahora pregunta si se quiere reemplazar cuando al mover
 * existe un archivo o carpeta con el mismo nombre.
 * ======================================================= */

// ---------- Ajustes iniciales ----------
$root            = ROOT_DIR;     // Carpeta raíz (Ya definida como constante en verificar_sesion.php)
$carpetaRelativa = $_GET['carpeta'] ?? '';      // Carpeta actual (relativa)
$error           = $_GET['error']    ?? '';      // Código de error
$rutaActual      = realpath($root . '/' . $carpetaRelativa);

// ✅ Comprobación de que la ruta actual está dentro del ROOT_DIR y es válida.
if (!$rutaActual || !estaDentroDe($rutaActual, $root)) {
    die("Ruta inválida.");
}

// Crear carpeta de papelera si no existe
$papelera = $root . '/.papelera_creawebes';
if (!is_dir($papelera)) {
    mkdir($papelera, 0775, true);
    file_put_contents($papelera . '/.htaccess', "Deny from all"); // protege acceso directo
}



// MODIFICACIÓN 1: DEFINIR ARCHIVOS DE SISTEMA A OCULTAR
$archivosSistema = [
    // --- CARPETAS DE SISTEMA ---
    '_backups',
    'Historiales',
    '_informes_restore',
    '.instalador_creawebes',
    'src',
    'vendor',
    'partials',
    'assets',
    'test',
    'pija',
    'memory_backups',

    // --- HERRAMIENTAS DE DESARROLLO (NO DEBEN SER VISIBLES) ---
    '.agents',
    '.git',
    '.github',
    '.vscode',
    '.cursorrules',
    '.phpunit.cache',
    'phpunit.xml',
    'composer.json',
    'composer.lock',

    // --- CONFIGURACIÓN Y ENTORNO ---
    '.env',
    '.env.example',
    '.gitignore',
    '.htaccess',
    'autoload.php',

    // --- DOCUMENTACIÓN INTERNA ---
    'MEMORY.md',
    '_MEMORY.bak.md',
    'Auditoria_Inicial.md',
    'README.md',
    'readme.txt',

    // --- ARCHIVOS DEL PANEL (PHP ENDPOINTS) ---
    'archivocrear.php',
    'backup.php',
    'backup_cron.php',
    'backup_proc.php',
    'buscar.php',
    'conectados.php',
    'comprimir.php',
    'CrearNuevo Archivo.php',
    'crear_hash.php',
    'default.php',
    'latido.php',
    'api_carpetas.php',
    'api_contador.php',
    'descomprimir.php',
    'download.php',
    'editor.php',
    'funciones.php',
    'gestionar_usuarios.php',
    'gestor_compartir.php',
    'index.php',
    'login.php',
    'logout.php',
    'preview.php',
    'restaurar.php',
    'restaurar_proc.php',
    'restaurar_proc_completo.php',
    'test_cron.php',
    'usuarios.php',
    'ver_backups.php',
    'verificar_sesion.php',
    'chat_api.php',

    // --- DATOS JSON DEL SISTEMA ---
    'usuarios_activos.json',
    '.usuarios_online.json',
    'actividad_usuarios.json',
    'mensajes.json',
    'compartidos.json',

    // --- ARCHIVOS MULTIMEDIA DEL SISTEMA ---
    'llamada.mp3',
    'relampago.mp3',
    'notificacion.mp3',
    'puto.jpg',

    // --- OTROS ---
    'google66cdb90433076f9f.html',
    'sitemap.xml',
    '.papelera_creawebesindex.php'
];
// Agregar dinámicamente cualquier backup_*.zip suelto en la raíz
foreach (glob(ROOT_DIR . '/backup_*.zip') as $bkFile) {
    $archivosSistema[] = basename($bkFile);
}
require_once __DIR__ . '/partials/helpers.php';

/* ---------- API para <select> de destinos ---------- */
if (isset($_GET['listar'])) {
    header('Content-Type: application/json');
    $todas  = listarCarpetas($root);
    $actual = $_GET['actual'] ?? '';
    $mostrarSistema = isset($_GET['sistema']) && $_GET['sistema'] === '1' && $esAdmin;
    
    // Filtrar carpetas de sistema si el toggle está apagado
    if (!$mostrarSistema) {
        $carpetasSistema = array_filter($archivosSistema, function($item) use ($root) {
            return is_dir($root . '/' . $item);
        });
        $todas = array_filter($todas, function($carpeta) use ($carpetasSistema) {
            foreach ($carpetasSistema as $oculta) {
                if ($carpeta === $oculta || str_starts_with($carpeta, $oculta . '/')) {
                    return false;
                }
            }
            return true;
        });
    }
    
    echo json_encode(array_values(array_filter(
        array_merge([''], $todas),
        fn($c) => $c !== $actual       // quita la carpeta actual
    )));
    exit;
}

/* ---------- Acciones POST (delegadas a FileExplorerService) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion      = $_POST['accion']  ?? '';
    $objetivoRel = $_POST['archivo'] ?? '';
    $rutaObjAbs  = $objetivoRel ? realpath($root . '/' . $objetivoRel) : null;
    
// ⛔ Bloquear acciones sobre carpeta 'usuarios' y '.papelera_creawebes' si no sos admin
if (
    isset($objetivoRel) &&
    (
        $objetivoRel === 'usuarios' ||
        str_starts_with($objetivoRel, 'usuarios/') ||
        $objetivoRel === '.papelera_creawebes'
    )
) {
    $accionesNoPermitidas = ['eliminar', 'renombrar', 'mover', 'duplicar'];
    $esAccionCompartir = $accion === 'compartir';

    if (in_array($accion, $accionesNoPermitidas) && !$esAdmin && !$esAccionCompartir) {
        $esDueño = false;
        if (!empty($nombreCarpetaUsuarioLogueado) && str_starts_with($objetivoRel, 'usuarios/' . $nombreCarpetaUsuarioLogueado . '/')) {
            $esDueño = true;
        }
        if (!$esDueño) {
            header('Location: index.php?carpeta=' . urlencode($carpetaRelativa) . '&error=protegido');
            exit;
        }
    }
    if (
        in_array($accion, ['eliminar', 'renombrar', 'mover']) &&
        in_array($objetivoRel, ['usuarios', '.papelera_creawebes'])
    ) {
        header('Location: index.php?carpeta=' . urlencode($carpetaRelativa) . '&error=protegido');
        exit;
    }
}

    if ($objetivoRel && $rutaObjAbs && !estaDentroDe($rutaObjAbs, $root)) {
        header('Location: index.php?carpeta=' . urlencode($carpetaRelativa) . '&error=ruta_invalida');
        exit;
    }

    require_once __DIR__ . '/src/autoload.php';
    $explorerService = new \Infrastructure\Service\FileExplorerService($root);
    $result = null;

    /* ---------- Subida de archivo ---------- */
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $result = $explorerService->upload($rutaActual, $_FILES['archivo']);
        if (isset($result['error'])) {
            header('Location: index.php?carpeta=' . urlencode($carpetaRelativa) . '&error=' . $result['error']);
        } else {
            header('Location: index.php?carpeta=' . urlencode($carpetaRelativa));
        }
        exit;
    }

    /* ---------- Resto de acciones ---------- */
    switch ($accion) {
        case 'vaciar_papelera':
            $result = $explorerService->emptyTrash($_POST['clave'] ?? '', $carpetaRelativa);
            break;

        case 'restaurar':
            $result = $explorerService->restoreFromTrash($objetivoRel, $carpetaRelativa);
            break;

        case 'eliminar':
            $result = $explorerService->delete($rutaObjAbs, $carpetaRelativa);
            break;

        case 'renombrar':
            $result = $explorerService->rename($rutaObjAbs, $_POST['nuevo_nombre'] ?? '', $carpetaRelativa);
            break;

        case 'duplicar':
            $result = $explorerService->duplicate($rutaObjAbs, $carpetaRelativa);
            break;

        case 'crear_carpeta':
            $result = $explorerService->createFolder($rutaActual, $_POST['nueva_carpeta'] ?? '');
            break;

        case 'crear_archivo':
            $result = $explorerService->createFile($rutaActual, $_POST['nombre_archivo'] ?? '', $_POST['contenido'] ?? '');
            break;

        case 'mover':
            $result = $explorerService->move($rutaObjAbs, trim($_POST['destino'] ?? ''), ($_POST['forzar'] ?? '') === '1', $carpetaRelativa);
            break;

        case 'restaurar_multiple':
            $archivos = json_decode($_POST['archivos_json'] ?? '[]', true);
            $result = $explorerService->restoreMultiple($archivos, $carpetaRelativa);
            break;

        case 'eliminar_multiple':
            $archivos = json_decode($_POST['archivos_json'] ?? '[]', true);
            $result = $explorerService->deleteMultiple($archivos, $carpetaRelativa);
            break;

        case 'mover_multiple':
            $archivos = json_decode($_POST['archivos_json'] ?? '[]', true);
            $result = $explorerService->moveMultiple($archivos, trim($_POST['destino'] ?? ''), ($_POST['forzar'] ?? '') === '1', $carpetaRelativa);
            break;
    }

    // Manejo unificado de la respuesta del servicio
    if ($result) {
        if (isset($result['error'])) {
            $errorParam = $result['error'];
            $extra = '';
            if ($errorParam === 'conflicto' && isset($result['archivo'], $result['destino'])) {
                $extra = '&archivo=' . urlencode($result['archivo']) . '&destino=' . urlencode($result['destino']);
            } elseif ($errorParam === 'conflicto_multiple' && isset($result['archivos'], $result['destino'])) {
                $extra = '&archivos=' . urlencode(json_encode($result['archivos'])) . '&destino=' . urlencode($result['destino']);
            }
            header('Location: index.php?carpeta=' . urlencode($carpetaRelativa) . '&error=' . $errorParam . $extra);
            exit;
        }
        if (isset($result['ok']) && is_string($result['ok'])) {
            header('Location: index.php?carpeta=' . urlencode($result['redirect'] ?? $carpetaRelativa) . '&ok=' . $result['ok']);
            exit;
        }
        if (isset($result['redirect'])) {
            header('Location: index.php?carpeta=' . urlencode($result['redirect']));
            exit;
        }
    }

    header('Location: index.php?carpeta=' . urlencode($carpetaRelativa));
    exit;
}



// =================================================================================
// =========== INICIO DE LA MODIFICACIÓN PARA EL ORDEN PERSONALIZADO ===============
// =================================================================================

// ---------- ORDEN PERSONALIZADO PARA LA RAÍZ ----------
// Define el orden estético deseado para la carpeta de inicio.
$ordenPersonalizado = [
    '.papelera_creawebes',
    'usuarios',
    'Creawebes',
    'NoticiasDigitales',
    'Whatsappmasivo'
];
// --------------------------------------------------------

// Primero, obtenemos la lista de archivos y carpetas como antes.
$items = array_filter(scandir($rutaActual), function($i) use ($rutaActual, $root) {
    if ($i === '.' || $i === '..') return false;
    // Ocultar la papelera si no estamos en la raíz
    if ($i === '.papelera_creawebes' && $rutaActual !== $root) return false;
    return true;
});

// Ahora, aplicamos la lógica de ordenación condicional.
if (trim($carpetaRelativa) === '' && !isset($_GET['orden'])) {
// CASO 1: Estamos en la raíz Y NO se ha seleccionado un orden. Usamos el orden personalizado.
    usort($items, function ($a, $b) use ($ordenPersonalizado) {
        $posA = array_search($a, $ordenPersonalizado);
        $posB = array_search($b, $ordenPersonalizado);

        // Si ambos ítems están en la lista personalizada, los ordenamos según su posición en ella.
        if ($posA !== false && $posB !== false) {
            return $posA <=> $posB;
        }
        // Si solo A está en la lista, va primero.
        if ($posA !== false) {
            return -1;
        }
        // Si solo B está en la lista, va primero.
        if ($posB !== false) {
            return 1;
        }
        // Si ninguno está en la lista, los ordenamos alfabéticamente al final.
        return strcasecmp($a, $b);
    });

} else {
    // CASO 2: Estamos en otra carpeta O SÍ se seleccionó un orden. Usamos la lógica normal.
    $orden = $_GET['orden'] ?? 'nombre';
    $dir   = $_GET['dir'] ?? 'asc';

    usort($items, function ($a, $b) use ($rutaActual, $orden, $dir) {
        $pa = $rutaActual . '/' . $a;
        $pb = $rutaActual . '/' . $b;
        $esDirA = is_dir($pa);
        $esDirB = is_dir($pb);

        // Siempre mostrar carpetas primero
        if ($esDirA && !$esDirB) return -1;
        if (!$esDirA && $esDirB) return 1;

        // Lógica de ordenación por columna
        switch ($orden) {
            case 'fecha': $res = filemtime($pa) <=> filemtime($pb); break;
            case 'tipo':
                $extA = strtolower(pathinfo($a, PATHINFO_EXTENSION));
                $extB = strtolower(pathinfo($b, PATHINFO_EXTENSION));
                $res = $extA <=> $extB;
                if ($res === 0) $res = strcasecmp($a, $b);
                break;
            case 'nombre':
            default:
                $res = strcasecmp($a, $b);
        }
        return $dir === 'desc' ? -$res : $res;
    });
}
// =================================================================================
// ================ FIN DE LA MODIFICACIÓN PARA EL ORDEN PERSONALIZADO ===============
// =================================================================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Explorador – <?= htmlspecialchars($carpetaRelativa ?: 'Inicio') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/panel.css?v=<?= time() ?>">
</head>
<body>
<?php
$archivoActividad = __DIR__ . '/actividad_usuarios.json';
$actividad = file_exists($archivoActividad) ? json_decode(file_get_contents($archivoActividad), true) : [];
$usuariosConectados = [];
$ahora = time();
$maxInactividad = 120; // segundos
foreach ($actividad as $usuario => $datos) {
    if (!isset($datos['ultima_conexion'])) continue;
    $tsConexion = strtotime($datos['ultima_conexion']);
    $tsDesconexion = isset($datos['ultima_desconexion']) ? strtotime($datos['ultima_desconexion']) : 0;
    if ($tsConexion > $tsDesconexion && ($ahora - $tsConexion) < $maxInactividad) {
        $usuariosConectados[$usuario] = $datos;
    }
}
?>

<div class="explorador">

    <!-- Header / Usuario -->
    <div style="display:flex; justify-content:flex-end; align-items:center; margin-bottom: 2rem;">
        <span style="font-weight: 500; margin-right: 1.5rem; color: var(--color-texto-ligero);">
            👤 Usuario: <?= htmlspecialchars($_SESSION['usuario'] ?? 'Invitado') ?>
        </span>
        <a href="logout.php" onclick="sessionStorage.removeItem('sistemaVisible');" class="btn-top" style="text-decoration:none; padding: 0.5rem 1rem;">
           🔓 Cerrar sesión
        </a>
    </div>

    <!-- Banner visual (Hereda estilos del CSS ahora) -->
    <h1 class="titulo-principal">Creawebes</h1>
    <p class="titulo-sub">Plataforma profesional de gestión de archivos</p>


    <?php if ($error === 'existe'): ?><div class="error">❌ Ya existe un archivo o carpeta con ese nombre.</div><?php endif; ?>
    <?php if ($error === 'ruta_invalida'): ?><div class="error">❌ Error: Ruta no válida o fuera del alcance permitido.</div><?php endif; ?>
    <?php if ($error === 'clave'): ?><div class="error">❌ Contraseña incorrecta.</div><?php endif; ?>
    <?php if ($error === 'protegido_index'): ?><div class="error">🚫 El archivo root/index.php está protegido contra esta acción. Active el "Override temporal" si es necesario.</div><?php endif; ?>
    <?php if (isset($_GET['ok']) && $_GET['ok'] === 'papelera_vaciada'): ?><div style="background:#e8f5e9;color:#2e7d32;padding:.8rem;border-radius:.5rem;margin-bottom:1rem;">✅ Papelera vaciada correctamente.</div><?php endif; ?>
    <?php if ($error === 'protegido'): ?><div class="error">🚫 Acción no permitida sobre carpeta protegida.</div><?php endif; ?>

    <!-- ========================================================= -->
    <!-- ✅ INICIO: ESTRUCTURA DE ACCIONES REORGANIZADA         -->
    <!-- ========================================================= -->
    <div class="panel-acciones">
        <div class="acciones-principales">
            <button class="btn-top" onclick="crearCarpeta()">📁 Nueva carpeta</button>
            <button class="btn-top" onclick="mostrarSubida()">📤 Subir archivo</button>
            <button class="btn-top" onclick="window.open('CrearNuevo%20Archivo.php?carpeta=<?= urlencode($carpetaRelativa) ?>', '_blank')">📝 Crear documento</button>
        </div>
 
        <div class="acciones-secundarias">
            <div class="grupo-busqueda">
                <span>🔍</span>
                <form onsubmit="return false;" style="margin: 0;">
                    <input type="text" name="q" id="buscador" placeholder="Buscar...">
                </form>
            </div>

            <div style="position: relative;">
                <button class="btn-top" onclick="toggleMenuOrden()">Ordenar ▾</button>
                <div id="menuOrden" style="display:none;position:absolute;right:0;top:calc(100% + 5px);background:#fff;border:1px solid #ddd;padding:.5rem 1rem;border-radius:.5rem;box-shadow:var(--sombra-caja);z-index:1000;min-width: 220px;">
                    <?php
                        $orden_actual = $_GET['orden'] ?? 'nombre';
                        $dir_actual   = $_GET['dir'] ?? 'asc';
                        $base  = 'index.php?carpeta=' . urlencode($carpetaRelativa);
                        function linkOrden($nombre, $campo, $dir, $actualOrden, $actualDir, $base) {
                            $activo = ($campo === $actualOrden && $dir === $actualDir) ? 'font-weight:bold;color:var(--color-primario);' : 'color:#333;';
                            $url = "$base&orden=$campo&dir=$dir";
                            return "<div style='margin: .2rem 0;'><a href=\"$url\" style=\"$activo text-decoration:none; display:block; padding:.2rem 0; font-size: 0.95rem; font-family: Lato, sans-serif;\">$nombre</a></div>";
                        }
                        echo linkOrden('Nombre (A-Z)', 'nombre', 'asc', $orden_actual, $dir_actual, $base);
                        echo linkOrden('Nombre (Z-A)', 'nombre', 'desc', $orden_actual, $dir_actual, $base);
                        echo linkOrden('Tipo (A-Z)', 'tipo', 'asc', $orden_actual, $dir_actual, $base);
                        echo linkOrden('Tipo (Z-A)', 'tipo', 'desc', $orden_actual, $dir_actual, $base);
                        echo linkOrden('Fecha (recientes primero)', 'fecha', 'desc', $orden_actual, $dir_actual, $base);
                        echo linkOrden('Fecha (más antiguas)', 'fecha', 'asc', $orden_actual, $dir_actual, $base);
                    ?>
                </div>
            </div>

                      <div class="herramientas-dropdown">
                <button class="btn-top" onclick="toggleHerramientas()">🛠️ Herramientas ▾</button>
                <div id="herramientasContenido" class="herramientas-contenido">
                    
                    <?php if ($esAdmin): ?>
                        <form id="backupForm" method="POST" action="backup.php">
                            <input type="hidden" name="accion" value="backup">
                            <button class="dropdown-item" type="submit" onclick="return confirm('¿Crear copia de seguridad?')">♻️ Copia de seguridad</button>
                        </form>
                         <form method="GET" action="restaurar.php">
                            <button class="dropdown-item" type="submit" onclick="return confirm('¿Ir a restaurar una copia?')">🔄 Restaurar copia</button>
                        </form>
                    <?php endif; ?>

                    <button class="dropdown-item" onclick="window.open('https://script.google.com/macros/s/AKfycbwUfLvcB_o9k3fMW52Iz984UoFN6TYc0K9ntPpq1_MbA-Drvj_MOn4ur0-6aDkfu_x8/exec', '_blank')">📅 Agenda</button>
                    <button class="dropdown-item" onclick="abrirVentanaConectados()" id="botonConectados">🟢 Conectados (<?= count($usuariosConectados) ?>)</button>
                    <button class="dropdown-item" onclick="abrirVideollamadaGeneral()">📹 Videollamada general</button>
                    
                    <?php if ($esAdmin): ?>
                        <?php if ($carpetaRelativa === '' || $carpetaRelativa === '.'): ?>
                            <button class="dropdown-item" type="button" onclick="alternarSistema()" id="botonSistema">🔐 Archivo de sistema</button>
                        <?php endif; ?>
                       <button class="dropdown-item" type="button" onclick="window.open('gestionar_usuarios.php', '_blank')">👥 Gestión de usuarios</button>
                   <?php endif; ?>
                </div>
            </div>
        </div>
            </div>
    <!-- ========================================================= -->
    <!-- ✅ FIN: ESTRUCTURA DE ACCIONES REORGANIZADA            -->
    <!-- ========================================================= -->

    <div id="resultadosBusqueda"></div>

    <!-- ====================================================== -->
    <!-- 🚀 ZONA DRAG & DROP - Subida de archivos               -->
    <!-- ====================================================== -->
    <div id="subida" style="display:none; margin: 1.5rem 0;">
        <div id="dropzone" class="dropzone">
            <div class="dropzone-icon">📂</div>
            <p class="dropzone-titulo">Arrastrá tu archivo aquí</p>
            <p class="dropzone-sub">o hacé clic para seleccionarlo</p>
            <form id="uploadForm" method="POST" enctype="multipart/form-data">
                <input type="file" name="archivo" id="fileInput" required style="display:none;">
                <button type="button" class="btn-top" onclick="document.getElementById('fileInput').click()" style="margin-top:1rem;">
                    🗂️ Elegir archivo
                </button>
            </form>
            <div id="dropzone-preview" style="display:none; margin-top: 1rem;">
                <span id="dropzone-filename" style="font-weight:600; color: var(--color-primario);"></span>
                <button type="button" class="btn-top" id="btnUploadConfirm" style="margin-left: 1rem; background: linear-gradient(135deg, #16a34a, #15803d);">
                    ⬆️ Subir ahora
                </button>
            </div>
        </div>
    </div>

    <?php if ($carpetaRelativa === '.papelera_creawebes'): ?>
        <form method="post" style="margin: 1rem 0; background: #fff3e0; padding: 1rem; border-radius: .5rem;" onsubmit="return confirm('¿Vaciar la papelera permanentemente?')">
            <input type="hidden" name="accion" value="vaciar_papelera">
            <div style="display:inline-flex; align-items:center; gap:.5rem;">
                <label for="claveInput" style="font-weight:bold;">Para vaciar, introduce la contraseña:</label>
                <input type="password" name="clave" id="claveInput" placeholder="Contraseña" required style="padding:.5rem; border: 1px solid var(--color-borde); border-radius: 4px;">
                <button type="button" onclick="document.getElementById('claveInput').type = document.getElementById('claveInput').type === 'password' ? 'text' : 'password'" style="padding:.4rem .6rem; background:#eee; border:1px solid #ccc; border-radius:4px; cursor:pointer; font-size: 1.2rem; line-height: 1;">👁️</button>
            </div>
            <button type="submit" style="background:#d32f2f;color:white;padding:.5rem 1rem;border:none;border-radius:5px;cursor:pointer;margin-left:1rem; font-weight: bold;">🧹 Vaciar papelera</button>
        </form>
    <?php endif; ?>

    <ul class="explorador">
<?php foreach ($items as $item):
    $rutaCompleta = $rutaActual . '/' . $item;
    $relativa     = obtenerRutaRelativa($root, $rutaCompleta);
    $dir          = is_dir($rutaCompleta);
    $clasesCss    = $dir ? 'carpeta' : 'archivo';
    $estiloCss    = '';

    // ### MODIFICADO ### Lógica de visibilidad mejorada
    $esVisible = true;
    if ($carpetaRelativa === 'usuarios') {
        // Dentro de /usuarios, por defecto no se muestra nada
        $esVisible = false;
        if ($esAdmin) {
            $esVisible = true; // El admin ve todo
        } else {
            // Un usuario normal ve su propia carpeta y las compartidas con él
            if ($item === $nombreCarpetaUsuarioLogueado || in_array($item, $compartidasConmigo)) {
                $esVisible = true;
            }
        }
    }

    if ($carpetaRelativa === '' && $item === 'usuarios') {
        if (!$esAdmin && empty($nombreCarpetaUsuarioLogueado)) $esVisible = false; // Ocultar /usuarios si no eres admin y no tienes carpeta
    }
    
    // Si la lógica anterior decide que no es visible, saltamos el item
    if (!$esVisible) continue;
    
    // 💠 Color especial para la carpeta "usuarios" en raíz
    if ($carpetaRelativa === '' && $item === 'usuarios' && is_dir($rutaCompleta)) {
        $estiloCss = 'background-color: #e3f2fd; font-weight: bold;';
    }

      // 🛠️ Archivos del sistema: ocultar y opcionalmente marcar
    // Se ocultan solo si están en la raíz y son parte de $archivosSistema
    if (in_array($item, $archivosSistema)) {
        $clasesCss .= ' archivo-sistema'; // Siempre añadir la clase para que el botón "Archivo de sistema" funcione en JS
        
        // Solo ocultar y marcar con color si está en la carpeta raíz (vacía o '.')
        if ($carpetaRelativa === '' || $carpetaRelativa === '.') {
            $estiloCss .= 'display:none;';
            // No aplicar color de fondo a estos dos archivos específicos si se muestran
            // ya que son generalmente públicos y no necesitan ser "marcados" visualmente
            // al estar ocultos.
            if ($item !== 'sitemap.xml' && $item !== 'google66cdb90433076f9f.html') {
                $estiloCss .= ' background-color: #fff5cc;';
            }
        }
        // Si no está en la raíz, no se añade 'display:none' al $estiloCss, por lo que será visible.
        // Tampoco se le aplicará el color de fondo específico de "archivo de sistema oculto".
    }
?>
    <li class="<?= $clasesCss ?>" style="<?= $estiloCss ?>" data-nombre="<?= htmlspecialchars($relativa) ?>" data-ruta="<?= htmlspecialchars($relativa) ?>" title="<?= htmlspecialchars($item) ?>">
        <span style="font-size: 1.2rem;">
        <?php
        if ($dir && $item === 'usuarios') echo '👥';
        elseif ($dir && $carpetaRelativa === 'usuarios') echo '👤';
        elseif ($dir && $item === '.papelera_creawebes') echo '🗑️';
        elseif ($dir) { // ### MODIFICADO ### Lógica para el icono de compartir
            echo '📁'; // Icono base de carpeta
            $esCompartida = false;
            // Condición 1: La carpeta es mía y la estoy compartiendo con alguien
            if (isset($infoCompartidos[$relativa])) {
                $esCompartida = true;
            }
            // Condición 2: Es una carpeta de otro usuario compartida conmigo (solo visible en /usuarios)
            if ($carpetaRelativa === 'usuarios' && in_array($item, $compartidasConmigo)) {
                $esCompartida = true;
            }
            
            if ($esCompartida) {
                echo '<span title="Carpeta compartida" style="color: var(--color-primario); margin-left: 5px;">🔗</span>';
            }
        }
        else echo iconoArchivo($item);
        ?>
        </span>

        <?php if ($dir): ?>
            <a href="index.php?carpeta=<?= urlencode($relativa) ?>"><?= htmlspecialchars($item) ?></a>
        <?php else: ?>
            <a href="<?= htmlspecialchars($relativa) ?>" target="_blank"><?= htmlspecialchars($item) ?></a>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
</ul>

    <?php if ($carpetaRelativa && $carpetaRelativa !== '.'): $padre = dirname($carpetaRelativa); $back  = $padre === '.' ? '' : '?carpeta=' . urlencode($padre); ?>
        <div class="volver"><a href="index.php<?= $back ?>">⬅️ Volver</a></div>
    <?php endif; ?>

</div> <!-- /explorador contenedor -->

    <footer class="footer">© <?= date('Y') ?> Creawebes. Todos los derechos reservados.</footer>

    <div id="menu" class="menu"></div>
<?php include __DIR__ . '/partials/modales.php'; ?>
</div>

<!-- ### NUEVO ### Pasar variables de PHP a JS para la lógica de compartir -->
<?php
$panelConfig = [
    'esAdmin' => $esAdmin,
    'sistemaPassword' => $_ENV['SISTEMA_PASSWORD'] ?? '',
    'miCarpeta' => $nombreCarpetaUsuarioLogueado,
    'todosLosUsuarios' => array_values($carpetasDeUsuarios),
    'carpetaRelativaUrlEncoded' => urlencode($carpetaRelativa),
    'carpetaRelativaHtml' => htmlspecialchars($carpetaRelativa, ENT_QUOTES),
    'carpetaRelativaSlashes' => addslashes($carpetaRelativa),
    'enPapelera' => ($carpetaRelativa === '.papelera_creawebes'),
    'miUsuarioDesdeIndex' => $_SESSION['usuario'] ?? ''
];
?>
<script id="panel-config" type="application/json">
<?= json_encode($panelConfig) ?>
</script>
<script src="assets/panel.js?v=<?= time() ?>"></script>



</body>
</html>