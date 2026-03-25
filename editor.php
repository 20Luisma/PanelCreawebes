<?php
require_once __DIR__ . '/verificar_sesion.php';
require_once __DIR__ . '/src/autoload.php';

use Infrastructure\Service\FileEditorService;
use Presentation\Controller\FileEditorController;

// =========================================================
// ✅ INICIO: GESTIÓN DE SEGURIDAD (CSRF, Nonce, Cabeceras)
// =========================================================
if (!isset($_SESSION['csrf_editor'])) {
    $_SESSION['csrf_editor'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_editor'];

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
// CSP Removido temporalmente para asegurar compatibilidad de botones y plugins ACE

$root = ROOT_DIR;
$archivoRel = trim($_POST['archivo'] ?? ($_GET['archivo'] ?? ''));

// Instanciación Clean Architecture
$editorService = new FileEditorService($root);
$controller = new FileEditorController($editorService, 'csrf_editor');

// Preparar datos para el Controller
$rutaOrig = realpath($root . '/' . ltrim($archivoRel, '/\\'));
$esIndexProtegido = $rutaOrig ? esRootIndex($rutaOrig) : false;
$indexExpira = $esIndexProtegido && !puedeTocarRootIndex();

// =========================================================
// ✅ MANEJO DE POST A TRAVÉS DEL CONTROLADOR
// =========================================================
$resultadoPost = $controller->handleRequest($_POST, $_SERVER, $_SESSION, $indexExpira);

if ($resultadoPost !== null) {
    if (isset($resultadoPost['is_override_auth'])) {
        if ($resultadoPost['status'] === 'override_active' && $esAdmin) {
            habilitarOverrideRootIndex(10);
            header('Location: editor.php?archivo=' . urlencode($archivoRel));
            exit;
        } else {
             http_response_code(403);
             exit($resultadoPost['html']);
        }
    }
    
    if ($resultadoPost['status'] === 'success' && $esIndexProtegido && isset($_SESSION['override_root_index_active']) && $_SESSION['override_root_index_active']) {
        deshabilitarOverrideRootIndex();
    }
    
    // Mostramos el HTML de respuesta
    echo $resultadoPost['html'];
    exit;
}

// =========================================================
// ✅ PROTECCIÓN VISUAL root/index.php
// =========================================================
if (!$rutaOrig || !is_file($rutaOrig) || !estaDentroDe($rutaOrig, $root)) {
    die('❌ Archivo inválido o no permitido.');
}

if ($esIndexProtegido && !puedeTocarRootIndex()) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Acceso Restringido</title>
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Lato', sans-serif; background: #f0f4f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .modal-content { background: white; padding: 2rem; border-radius: 10px; width: 90%; max-width: 500px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); text-align: center; }
            .btn { background:#3949ab; color:#fff; border:none; padding:.8rem 1.5rem; border-radius:.4rem; font-weight:bold; cursor:pointer; margin: 10px; transition: background-color 0.2s ease; }
            .btn:hover { background-color: #21427d; }
            .error { color: #b20000; font-weight: bold; margin-bottom: 1rem; }
            .info { color: #333; margin-bottom: 1.5rem; }
        </style>
    </head>
    <body>
        <div class="modal-content">
            <h2>⚠️ Acceso Restringido</h2>
            <p class="error">El archivo <strong>root/index.php</strong> está protegido contra edición directa.</p>
            <?php if (!$esAdmin): ?>
                <p class="info">Solo un usuario con rol de administrador puede habilitar la edición temporal.</p>
                <button class="btn" onclick="window.close()">❌ Cerrar</button>
            <?php else: ?>
                <p class="info">Como administrador, puede habilitar la edición temporal por 10 minutos.</p>
                <form method="POST" action="">
                    <input type="hidden" name="archivo" value="<?= htmlspecialchars($archivoRel) ?>">
                    <input type="hidden" name="habilitar_override" value="1">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>"> 
                    <button type="submit" class="btn">✅ Habilitar Edición Temporal</button>
                    <button type="button" class="btn" onclick="window.close()">❌ Cancelar</button>
                </form>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$ext = strtolower(pathinfo($rutaOrig, PATHINFO_EXTENSION));
$permitidas = ['php','html','htm','js','css','json','xml','md','txt','java','py','ts'];
if (!in_array($ext, $permitidas)) die('❌ Tipo de archivo no editable.');

// Cargar estado inicial usando el Service
try {
    $contenido = $editorService->readFile($archivoRel);
    $mtime = $editorService->getFileTime($archivoRel);
} catch (\Exception $e) {
    die('❌ Error al leer el archivo original.');
}

?><!DOCTYPE html><html lang="es"><head>
<meta charset="UTF-8"><title>Editar <?= htmlspecialchars($archivoRel) ?></title>
<style>
 body{margin:0;background:#f5f8ff;font-family:sans-serif}
 #editor{position:fixed;top:60px;bottom:0;left:0;right:0;font-size:16px}
 .barra{height:60px;background:#3949ab;color:#fff;display:flex;align-items:center;
        justify-content:space-between;padding:0 1rem}
 .barra h1{font-size:1rem;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
 .btn{background:#fff;color:#3949ab;border:none;padding:.5rem 1rem;border-radius:.3rem;
      font-weight:bold;cursor:pointer}.btn:hover{background:#e0e0e0}
</style></head><body>
<div class="barra">
 <h1>✍️ Editando: <?= htmlspecialchars($archivoRel) ?></h1>
 <button class="btn" onclick="guardar()">💾 Guardar</button>
 <button class="btn" onclick="deshacer()">↩️ Deshacer</button>
 <button class="btn" onclick="rehacer()">↪️ Rehacer</button>
 <button class="btn" onclick="buscar()">🔍 Buscar</button>
 <button class="btn" onclick="vistaPrevia()">👁️ Vista previa</button>
 <button class="btn" onclick="copiar()">📋 Copiar código</button>
</div>

<div id="editor"><?= htmlspecialchars($contenido) ?></div>

<form id="form" method="POST" style="display:none;">
 <textarea name="contenido" id="contenido"></textarea>
 <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
 <input type="hidden" name="mtime" value="<?= htmlspecialchars($mtime) ?>">
 <input type="hidden" name="archivo" value="<?= htmlspecialchars($archivoRel) ?>">
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ace.js"></script>
<script>
const ed=ace.edit("editor");
ed.setTheme("ace/theme/github");

const ext = "<?= $ext ?>";
let mode = "text";
switch(ext) {
    case 'php': mode = 'php'; break;
    case 'html': case 'htm': mode = 'html'; break;
    case 'js': mode = 'javascript'; break;
    case 'css': mode = 'css'; break;
    case 'json': mode = 'json'; break;
    case 'xml': mode = 'xml'; break;
    case 'md': mode = 'markdown'; break;
    case 'txt': mode = 'text'; break;
    case 'java': mode = 'java'; break;
    case 'py': mode = 'python'; break;
    case 'ts': mode = 'typescript'; break;
}
ed.session.setMode("ace/mode/" + mode);
ed.setOptions({fontSize:"14px",showPrintMargin:false,useWrapMode:true});

function b64EncodeUnicode(str){
  const bytes = new TextEncoder().encode(str);
  let bin = "";
  bytes.forEach(b => bin += String.fromCharCode(b));
  return btoa(bin);
}

function guardar(){
  document.getElementById('contenido').value = b64EncodeUnicode(ed.getValue());
  document.getElementById('form').submit();
}
document.addEventListener('keydown',e=>{
  if((e.ctrlKey||e.metaKey)&&e.key==='s'){e.preventDefault();guardar();}
});


function buscar() {
  ed.execCommand("find");
}
function copiar() {
  const texto = ed.getValue();
  const enIframe = window.self !== window.top;
  if (navigator.clipboard && location.protocol === 'https:' && !enIframe) {
    navigator.clipboard.writeText(texto).then(() => {
      mostrarMensajeCopiado();
    }).catch(() => {
      fallbackCopiar(texto);
    });
  } else {
    fallbackCopiar(texto);
  }
}
function fallbackCopiar(texto) {
  const textarea = document.createElement('textarea');
  textarea.value = texto;
  textarea.style.position = 'fixed';
  textarea.style.opacity = 0;
  document.body.appendChild(textarea);
  textarea.focus(); textarea.select();
  try {
    const exito = document.execCommand('copy');
    if (exito) mostrarMensajeCopiado();
    else alert('❌ No se pudo copiar.');
  } catch (err) {
    alert('❌ No se pudo copiar.');
  }
  document.body.removeChild(textarea);
}
function mostrarMensajeCopiado() {
  const msg = document.createElement('div');
  msg.textContent = '✅ Copiado al portapapeles.';
  msg.style.position = 'fixed';
  msg.style.bottom = '1rem';
  msg.style.right = '1rem';
  msg.style.background = '#dff0d8';
  msg.style.color = '#3c763d';
  msg.style.padding = '1rem';
  msg.style.borderRadius = '8px';
  msg.style.boxShadow = '0 0 10px rgba(0,0,0,.2)';
  msg.style.zIndex = 9999;
  document.body.appendChild(msg);
  setTimeout(() => msg.remove(), 3000);
}

function vistaPrevia() {
    const extension = "<?= $ext ?>";
    if (extension === 'php') {
        alert('La vista previa no está disponible para archivos PHP por seguridad. Puedes guardar y ver el resultado en el navegador.');
        return;
    }

    const contenido = ed.getValue();
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'preview.php';
    form.target = '_blank';

    const inputCodigo = document.createElement('input');
    inputCodigo.type = 'hidden';
    inputCodigo.name = 'codigo';
    inputCodigo.value = b64EncodeUnicode(contenido);

    const inputExt = document.createElement('input');
    inputExt.type = 'hidden';
    inputExt.name = 'extension';
    inputExt.value = extension;

    form.appendChild(inputCodigo);
    form.appendChild(inputExt);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
function deshacer() {
    ed.undo();
}
function rehacer() {
    ed.redo();
}

</script>
<script>
const usuario = "<?= $_SESSION['usuario'] ?>";
const clave = "pestanas_" + usuario;

localStorage[clave] = (parseInt(localStorage[clave] || 0) + 1);

window.addEventListener('beforeunload', () => {
  const restantes = Math.max((parseInt(localStorage[clave] || 1)) - 1, 0);

  if (restantes === 0) {
    localStorage.removeItem(clave);
    navigator.sendBeacon('salir_rapido.php');
  } else {
    localStorage[clave] = restantes;
  }
});
</script>

</body></html>
