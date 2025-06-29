<?php
session_start();

$root = realpath(__DIR__);
$carpetaPorURL = $_GET['carpeta'] ?? '';
$mensaje = '';
$reemplazar = isset($_POST['forzar']) && $_POST['forzar'] === '1';

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

        $rutaDestinoAbs = $root . ($carpetaRel ? '/' . $carpetaRel : '');
        if (strpos(realpath($rutaDestinoAbs), $root) !== 0) {
            $mensaje = '❌ Carpeta no permitida.';
        } else {
            if (!is_dir($rutaDestinoAbs)) {
                mkdir($rutaDestinoAbs, 0777, true);
            }

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
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Crear nuevo archivo – Panel Creawebes</title>
  <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Lato',sans-serif;background:#f5f8ff;margin:0;padding:1rem}
    h1{margin-top:0;color:#3949ab}
    form{background:#fff;padding:1rem 1.5rem;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.05)}
    label{display:block;margin-top:1rem;font-weight:700}
    select,input{width:100%;padding:.6rem;border:1px solid #ccc;border-radius:6px;font-family:inherit}
    #editor{height:350px;border:1px solid #ccc;border-radius:6px;margin-top:1rem}
    button{background:#3949ab;color:#fff;border:none;padding:.6rem 1.5rem;border-radius:6px;font-weight:bold;margin-top:1rem;cursor:pointer}
    .alert{margin-top:1rem;padding:.8rem;border-radius:6px}
    .alert.ok{background:#d4edda;color:#155724}
    .alert.err{background:#fff3cd;color:#856404}
    .toolbar{margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap}
  </style>
</head>
<body>

<h1>📝 Crear nuevo archivo</h1>
<?php if($carpetaPorURL): ?>
  <h3>📂 Carpeta actual: <?= htmlspecialchars($carpetaPorURL) ?></h3>
<?php endif; ?>

<?php if($mensaje): ?>
  <div class="alert <?= str_starts_with($mensaje,'✅') ? 'ok':'err' ?>">
    <?= $mensaje ?>
  </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" style="margin-bottom:1rem;">
  <label for="archivoSubido">📂 Cargar archivo desde tu equipo</label>
  <input type="file" name="archivoSubido" id="archivoSubido" accept=".php,.html,.css,.js,.txt,.md" onchange="leerArchivoLocal(this)">
</form>

<form method="POST" onsubmit="beforeSubmit()">
  <label>Nombre (sin extensión)</label>
  <input type="text" name="nombre" placeholder="ej: contacto" required>

  <label>Extensión</label>
  <input type="text" name="extension" list="exts" placeholder="php, html, css…" required>
  <datalist id="exts">
    <option value="php"><option value="html"><option value="css">
    <option value="js"><option value="txt"><option value="md">
  </datalist>

  <label>Carpeta destino</label>
  <select name="carpeta">
    <option value="">/ (raíz)</option>
    <?php foreach ($carpetas as $c): ?>
      <option value="<?= htmlspecialchars($c['ruta']) ?>" <?= ($c['ruta'] === $carpetaPorURL) ? 'selected' : '' ?>>
        <?= htmlspecialchars($c['nombre']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <label>Código</label>
  <div id="editor"></div>
  <textarea id="codigo" name="codigo" style="display:none"></textarea>

  <div class="toolbar">
    <button type="button" onclick="copiar()">📋 Copiar</button>
    <button type="button" onclick="descargar()">📥 Descargar</button>
    <button type="button" onclick="limpiar()">🧹 Limpiar</button>
    <button type="button" onclick="buscar()">🔍 Buscar</button>
    <button type="submit">💾 Guardar archivo</button>
   </div>
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ace.js"></script>
<script>
const ed = ace.edit("editor");
ed.setTheme("ace/theme/github");
ed.session.setMode("ace/mode/php");
ed.setOptions({fontSize:"14px",showPrintMargin:false,wrap:true});

function beforeSubmit(){
  document.getElementById('codigo').value = btoa(unescape(encodeURIComponent(ed.getValue())));
}

function copiar() {
  navigator.clipboard.writeText(ed.getValue()).then(() => {
    alert("✅ Código copiado al portapapeles.");
  }).catch(() => {
    alert("❌ No se pudo copiar.");
  });
}

function descargar() {
  const contenido = ed.getValue();
  const blob = new Blob([contenido], { type: 'text/plain' });
  const enlace = document.createElement('a');
  enlace.href = URL.createObjectURL(blob);
  enlace.download = 'nuevo-archivo.txt';
  enlace.click();
}

function limpiar() {
  if (confirm("¿Vaciar todo el código?")) {
    ed.setValue("");
  }
}

function buscar() {
  const texto = prompt("🔍 Ingresá el texto a buscar:");
  if (texto) {
    ed.find(texto, {
      backwards: false,
      wrap: true,
      caseSensitive: false,
      wholeWord: false,
      regExp: false
    });
  }
}

function leerArchivoLocal(input) {
  const archivo = input.files[0];
  if (!archivo) return;

  const lector = new FileReader();
  lector.onload = function(e) {
    ed.setValue(e.target.result, -1);

    const nombreCompleto = archivo.name;
    const partes = nombreCompleto.split('.');
    if (partes.length > 1) {
      const extension = partes.pop().toLowerCase();
      const nombreSinExt = partes.join('.');
      document.querySelector('input[name="nombre"]').value = nombreSinExt;
      document.querySelector('input[name="extension"]').value = extension;

      const modos = { php: "php", js: "javascript", html: "html", css: "css", txt: "text", md: "markdown" };
      ed.session.setMode("ace/mode/" + (modos[extension] || "text"));
    }
  };
  lector.readAsText(archivo);
}
</script>


</body>
</html>
