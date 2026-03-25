<?php
require_once __DIR__ . '/verificar_sesion.php';
require_once __DIR__ . '/src/autoload.php';

use Infrastructure\Service\FileExplorerService;

$root = realpath(__DIR__);
$carpetaPorURL = $_GET['carpeta'] ?? '';
$mensaje = '';

$isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

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
        $nombreCompleto = $nombreArchivo;
        if (!str_ends_with($nombreCompleto, ".$extManual")) {
            $nombreCompleto .= ".$extManual";
        }

        $rutaDestinoAbs = $root . ($carpetaRel ? '/' . $carpetaRel : '');
        $realDest = realpath($rutaDestinoAbs);

        if (!$realDest || strpos($realDest, $root) !== 0) {
            $mensaje = '❌ Carpeta no permitida.';
        } else {
            if (!is_dir($rutaDestinoAbs)) {
                mkdir($rutaDestinoAbs, 0777, true);
            }

            // Anti-sobrescritura: genera nombre disponible
            $nombreSinExt = pathinfo($nombreCompleto, PATHINFO_FILENAME);
            $nombreFinal = $nombreSinExt;
            $contador = 2;
            while (file_exists($rutaDestinoAbs . '/' . $nombreFinal . '.' . $extManual)) {
                $nombreFinal = $nombreSinExt . $contador;
                $contador++;
            }
            $rutaFinal = $rutaDestinoAbs . '/' . $nombreFinal . '.' . $extManual;

            $codigo = base64_decode($codigoBase64);
            $ok = @file_put_contents($rutaFinal, $codigo);

            if ($ok === false) {
                $mensaje = '❌ No se pudo guardar. Revisá permisos.';
            } else {
                $carpetaCreadaAbs = dirname($rutaFinal);
                $carpetaRelCreada = ltrim(str_replace($root, '', $carpetaCreadaAbs), '/\\');
                header('Location: index.php?carpeta=' . urlencode($carpetaRelCreada));
                exit();
            }
        }
    }

    if ($isAjaxRequest) {
        echo $mensaje;
        exit;
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
    .spinner{display:inline-block;width:14px;height:14px;border:2px solid #ccc;border-top-color:#3949ab;border-radius:50%;animation:spin .6s linear infinite;vertical-align:middle;margin-left:6px}
    @keyframes spin{to{transform:rotate(360deg)}}
  </style>
</head>
<body>

<h1>📝 Crear nuevo archivo</h1>
<?php if($carpetaPorURL): ?>
  <h3>📂 Carpeta actual: <?= htmlspecialchars($carpetaPorURL) ?></h3>
<?php endif; ?>

<?php if($mensaje && !$isAjaxRequest): ?>
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
  <select name="carpeta" id="selectCarpeta">
    <option value="">/ (raíz)</option>
    <option value="" disabled>Cargando carpetas…</option>
  </select>

  <label>Código</label>
  <div id="editor"></div>
  <textarea id="codigo" name="codigo" style="display:none"></textarea>

<div class="toolbar">
  <button type="button" onclick="copiar()">📋 Copiar</button>
  <button type="button" onclick="descargar()">📥 Descargar</button>
  <button type="button" onclick="limpiar()">🧹 Limpiar</button>
  <button type="button" onclick="buscar()">🔍 Buscar</button>
  <button type="button" onclick="vistaPrevia()">👁️ Vista previa</button>
  <button type="button" onclick="deshacer()">↩️ Deshacer</button>
  <button type="button" onclick="rehacer()">↪️ Rehacer</button>
  <button type="submit">💾 Guardar archivo</button>
</div>
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ace.js"></script>
<script>
const ed = ace.edit("editor");
ed.setTheme("ace/theme/github");
ed.session.setMode("ace/mode/php");
ed.setOptions({fontSize:"14px",showPrintMargin:false,wrap:true});

// --- Carga AJAX de carpetas (no bloquea el render) ---
const carpetaPorURL = <?= json_encode($carpetaPorURL) ?>;

(function cargarCarpetas() {
  fetch('api_carpetas.php')
    .then(r => r.json())
    .then(carpetas => {
      const sel = document.getElementById('selectCarpeta');
      // Limpiar: dejar solo la raíz
      sel.innerHTML = '<option value="">/ (raíz)</option>';
      carpetas.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.ruta;
        opt.textContent = c.nombre;
        if (c.ruta === carpetaPorURL) opt.selected = true;
        sel.appendChild(opt);
      });
    })
    .catch(() => {
      const sel = document.getElementById('selectCarpeta');
      sel.innerHTML = '<option value="">/ (raíz) – Error cargando carpetas</option>';
    });
})();

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

async function vistaPrevia() {
  const nombre = document.querySelector('input[name="nombre"]').value.trim();
  const extension = document.querySelector('input[name="extension"]').value.trim();
  const codigo = ed.getValue();

  if (!nombre || !extension) {
    alert('❌ Debes ingresar un nombre y una extensión.');
    return;
  }

  const formData = new FormData();
  formData.set('codigo', btoa(unescape(encodeURIComponent(codigo))));
  formData.set('extension', extension);

  try {
    const response = await fetch('preview.php', {
      method: 'POST',
      body: formData
    });

    const blob = await response.blob();
    const vista = window.open();
    const url = URL.createObjectURL(blob);
    vista.location.href = url;
  } catch (err) {
    alert('❌ Error al previsualizar el archivo.');
    console.error(err);
  }
}

function deshacer() { ed.undo(); }
function rehacer() { ed.redo(); }
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
</body>
</html>