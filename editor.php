<?php
/* ---------- RUTAS Y VALIDACIONES ---------- */
$root       = realpath(__DIR__);
$backupBase = $root . '/respaldo';
$archivoRel = $_GET['archivo'] ?? '';
$rutaOrig   = realpath($root . '/' . $archivoRel);

if (!$rutaOrig || strpos($rutaOrig, $root) !== 0 || !is_file($rutaOrig)) {
    die('❌ Archivo inválido.');
}

$ext        = strtolower(pathinfo($rutaOrig, PATHINFO_EXTENSION));
$permitidas = ['php','html','htm','js','css','json','xml','md','txt','java','py','ts'];
if (!in_array($ext, $permitidas)) die('❌ Tipo no editable.');

/* ========= GUARDAR ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contenido = base64_decode($_POST['contenido'] ?? '');
    $ok        = @file_put_contents($rutaOrig, $contenido);

    /* --- Carpeta de backup: respaldo/<ruta>/<nombre.ext>/ --- */
    $rutaSub    = dirname($archivoRel);
    $nombreBase = basename($archivoRel); // Incluye .php, .html, etc.
    $dirBackup  = $backupBase . ($rutaSub ? '/' . $rutaSub : '') . '/' . $nombreBase;

    if (!is_dir($dirBackup)) mkdir($dirBackup, 0777, true);

    $stamp      = date('Ymd_His');
    $bakRel     = ($rutaSub ? "$rutaSub/" : '') . "$nombreBase/$stamp.txt";
    $bakAbs     = $backupBase . '/' . $bakRel;
    file_put_contents($bakAbs, $contenido);

    $msgColor = $ok ? ['#d4edda','#155724','✅ Cambios guardados']
                    : ['#fff3cd','#856404','⚠️ No se pudo guardar el original'];

    echo "<div style='padding:2rem;font-family:sans-serif;background:$msgColor[0];color:$msgColor[1];'>
            $msgColor[2].<br><br>
            <a href='respaldo/" . htmlspecialchars($bakRel) . "' download style='
               background:#3949ab;color:#fff;padding:.5rem 1rem;border-radius:.3rem;
               font-weight:bold;text-decoration:none;'>📥 Descargar backup</a><br><br>
            <a href='index.php?carpeta=" . urlencode(dirname($archivoRel)) . "'>⬅️ Volver al explorador</a>
          </div>";
    exit;
}

/* ---------- CARGAR PARA EDITAR ---------- */
$contenido = file_get_contents($rutaOrig);
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
 <button class="btn" onclick="verBackups()">🕘 Ver respaldos</button>
 <button class="btn" onclick="buscar()">🔍 Buscar</button>
 <button class="btn" onclick="copiar()">📋 Copiar código</button>
  </div>

<div id="editor"><?= htmlspecialchars($contenido) ?></div>

<form id="form" method="POST" style="display:none;">
 <textarea name="contenido" id="contenido"></textarea>
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ace.js"></script>
<script>
const ed=ace.edit("editor");
ed.setTheme("ace/theme/github");
ed.session.setMode("ace/mode/<?= $ext==='html'?'html':($ext==='py'?'python':$ext) ?>");
ed.setOptions({fontSize:"14px",showPrintMargin:false,useWrapMode:true});

function guardar(){
  document.getElementById('contenido').value = btoa(unescape(encodeURIComponent(ed.getValue())));
  document.getElementById('form').submit();
}
document.addEventListener('keydown',e=>{
  if((e.ctrlKey||e.metaKey)&&e.key==='s'){e.preventDefault();guardar();}
});

function verBackups() {
  const ruta = "<?= dirname($archivoRel) ? dirname($archivoRel) . '/' : '' ?>";
  const nombre = "<?= basename($archivoRel) ?>";
  window.open("ver_backups.php?carpeta=" + encodeURIComponent(ruta) + "&archivo=" + encodeURIComponent(nombre), "_blank", "width=600,height=500");
}
function buscar() {
  ed.execCommand("find");
}
function copiar() {
  const texto = ed.getValue();

  // Detectar si está embebido en iframe
  const enIframe = window.self !== window.top;

  // Si está en HTTPS y tiene clipboard API y no está en iframe o tiene permiso
  if (navigator.clipboard && location.protocol === 'https:' && !enIframe) {
    navigator.clipboard.writeText(texto).then(() => {
      mostrarMensajeCopiado();
    }).catch(() => {
      fallbackCopiar(texto);
    });
  } else {
    fallbackCopiar(texto); // método compatible con todo
  }
}

function fallbackCopiar(texto) {
  const textarea = document.createElement('textarea');
  textarea.value = texto;
  textarea.style.position = 'fixed';
  textarea.style.opacity = 0;
  document.body.appendChild(textarea);
  textarea.focus();
  textarea.select();

  try {
    const exito = document.execCommand('copy');
    if (exito) {
      mostrarMensajeCopiado();
    } else {
      alert('❌ No se pudo copiar. Seleccioná y copiá manualmente.');
    }
  } catch (err) {
    alert('❌ No se pudo copiar el código.');
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



</script>
</body></html>
