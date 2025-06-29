<?php
session_start();
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: login.php');
    exit;
}

$root       = realpath(__DIR__);
$dir        = $root . '/_backups/registros'; // ✅ Ruta corregida
$archivos   = glob($dir . '/*.zip');

// Ordenar por fecha (más recientes primero)
usort($archivos, fn($a, $b) => filemtime($b) - filemtime($a));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Restaurar copia – Panel Creawebes</title>
<link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Lato', sans-serif; background: #f5f8ff; margin: 0; padding: 2rem }
  h1 { margin: 0 0 1rem; color: #3949ab }
  table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; font-size: 0.95rem }
  th, td { padding: .6rem; border: 1px solid #ddd; text-align: left }
  th { background: #e3e9ff }
  tr:hover td { background: #f7f9ff }
  button { background: #3949ab; color: #fff; border: none; padding: .6rem 1.2rem; border-radius: 6px; font-weight: bold; cursor: pointer }
  #progresoBox { display: none; margin-top: 1.5rem }
  #barra { height: 22px; background: #d0d8ff; border-radius: 12px; overflow: hidden }
  #barra div { height: 100%; width: 0; background: #3949ab; color: #fff; text-align: center; line-height: 22px; font-size: .8rem }
  #log { background: #fff; border: 1px solid #ddd; padding: 1rem; margin-top: 1rem; height: 140px; overflow: auto; font-size: .8rem }
</style>
</head>
<body>

<h1>♻️ Restaurar una copia de seguridad</h1>

<?php if (!$archivos): ?>
  <p><strong>No hay copias en “_backups/registros”.</strong></p>
<?php else: ?>
<form id="frm">
  <table>
    <thead>
      <tr><th></th><th>Archivo</th><th>Fecha</th><th>Tamaño</th></tr>
    </thead>
    <tbody>
      <?php foreach ($archivos as $i => $zip): ?>
        <tr>
          <td><input type="radio" name="zip" value="<?= basename($zip) ?>" <?= $i == 0 ? 'checked' : '' ?>></td>
          <td><?= basename($zip) ?></td>
          <td><?= date('d-m-Y H:i', filemtime($zip)) ?></td>
          <td><?= number_format(filesize($zip) / 1024 / 1024, 2) ?> MB</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <button type="button" onclick="iniciar()">Restaurar</button>
</form>

<div id="progresoBox">
  <div id="barra"><div>0 %</div></div>
  <div id="log"></div>
  <div id="volverInicio" style="margin-top: 1.5rem; display: none;">
    <a href="index.php" style="text-decoration: none;">
      <button>🔙 Volver al inicio</button>
    </a>
  </div>
</div>
<?php endif; ?>

<script>
let total = 0, procesados = 0, sel;

function iniciar() {
  const r = document.querySelector('[name="zip"]:checked');
  if (!r) { alert('Selecciona un archivo'); return; }
  if (!confirm('¿Restaurar "' + r.value + '"?\nSe sobrescribirán archivos.')) return;

  sel = r.value;
  document.getElementById('frm').style.display = 'none';
  document.getElementById('progresoBox').style.display = 'block';
  log('Iniciando restauración de ' + sel);
  fetchPaso('init');
}

function fetchPaso(accion) {
  const body = new URLSearchParams({ accion, zip: sel, index: procesados });
  fetch('restaurar_proc.php', { method: 'POST', body })
    .then(r => r.json())
    .then(data => {
      if (data.error) { log('❌ ' + data.error); return; }
      if (accion === 'init') { total = data.total; procesados = 0; }
      if (accion === 'chunk') { procesados = data.procesados; }

      actualizarBarra();

      if (data.finalizado) {
        log('✅ Restauración completa.');
        alert('Se restauró exitosamente.');
        document.getElementById('volverInicio').style.display = 'block';
      } else {
        fetchPaso('chunk'); // siguiente tanda
      }
    })
    .catch(e => log('❌ ' + e));
}

function actualizarBarra() {
  const pct = Math.round(procesados * 100 / total);
  const bar = document.querySelector('#barra div');
  bar.style.width = pct + '%';
  bar.textContent = pct + '%';
}

function log(m) {
  const d = new Date().toLocaleTimeString();
  document.getElementById('log').innerHTML += '[' + d + '] ' + m + '<br>';
  document.getElementById('log').scrollTop = 999999;
}
</script>
</body>
</html>
