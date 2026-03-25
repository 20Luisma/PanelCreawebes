<?php
/**
 * restaurar.php – UI de restauración (panel)
 * - Lista ZIPs en /_backups/registros
 * - Lanza init/chunk contra restaurar_proc.php
 * - Al finalizar muestra botón "Ir al Panel"
 */

require_once __DIR__ . '/verificar_sesion.php';
if (!$esAdmin) { http_response_code(403); exit('Solo admin'); }

// Asegura que la sesión esté iniciada para CSRF y las variables de restauración
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION['csrf_restore'])) $_SESSION['csrf_restore'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_restore'];

$root = ROOT_DIR;
$dir  = $root . '/_backups/registros';
$zips = is_dir($dir) ? glob($dir.'/*.zip') : [];
usort($zips, fn($a,$b)=>(@filemtime($b)?:0)-(@filemtime($a)?:0));

// Autoarranque: POST limpio -> setea sesión y redirige GET
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion']??'')==='init') {
  if (!hash_equals($_SESSION['csrf_restore'], $_POST['csrf'] ?? '')) die('CSRF');
  $_SESSION['restore_zip'] = basename($_POST['zip']??'');
  // ¡Importante! Establecer la bandera de anulación del índice en la sesión
  $_SESSION['restore_allow_index'] = isset($_POST['override_index']) && $_POST['override_index']==='1';
  header('Location: restaurar.php'); exit;
}
?>
<!doctype html>
<meta charset="utf-8">
<title>Restaurar – Creawebes</title>
<link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
<style>
  body{font-family:Lato,system-ui;background:#f5f8ff;margin:0;padding:2rem}
  h1{color:#3949ab;margin:0 0 1rem}
  table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #ddd}
  th,td{padding:.6rem;border-bottom:1px solid #eee;font-size:.95rem}
  th{background:#e8ecff;text-align:left}
  button{background:#3949ab;color:#fff;border:0;border-radius:8px;padding:.6rem 1rem;font-weight:700;cursor:pointer}
  #progreso{display:none;margin-top:1rem}
  #barra{height:22px;background:#d0d8ff;border-radius:12px;overflow:hidden}
  #barra div{height:100%;width:0;background:#3949ab;color:#fff;text-align:center;line-height:22px;font-size:.8rem}
  #log{background:#fff;border:1px solid #ddd;padding:1rem;margin-top:1rem;height:180px;overflow:auto;font-size:.85rem}
  .box{background:#fff;border:1px solid #ddd;border-radius:10px;padding:1rem;margin:1rem 0}
  .btn-ok{background:#28a745}
  .btn-warn{background:#e67e22}
</style>

<h1>♻️ Restaurar una copia</h1>

<?php if (!$zips): ?>
  <p><strong>No hay copias en <code>_backups/registros</code>.</strong></p>
<?php else: ?>
<form id="frm" method="post">
  <input type="hidden" name="accion" value="init">
  <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
  <table>
    <thead><tr><th></th><th>Archivo</th><th>Fecha</th><th>Tamaño</th></tr></thead>
    <tbody>
      <?php foreach($zips as $i=>$z): ?>
      <tr>
        <td><input type="radio" name="zip" value="<?=htmlspecialchars(basename($z))?>" <?=$i===0?'checked':''?>></td>
        <td><?=htmlspecialchars(basename($z))?></td>
        <td><?=date('d-m-Y H:i', @filemtime($z)?:0)?></td>
        <td><?=number_format((@filesize($z)?:0)/1048576,2)?> MB</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="box">
    <label><input type="checkbox" name="override_index" value="1"> Permitir sobrescribir <code>root/index.php</code> (no recomendado)</label>
  </div>

  <button type="submit" id="btn">Iniciar restauración</button>
</form>

<div id="progreso">
  <div id="barra"><div>0%</div></div>
  <div id="log"></div>
</div>
<?php endif; ?>

<script>
const CSRF = <?=json_encode($csrf)?>;
<?php if (isset($_SESSION['restore_zip'])): ?>
const ZIP = <?=json_encode($_SESSION['restore_zip'])?>;
const PROC_URL = 'restaurar_proc.php';
document.addEventListener('DOMContentLoaded', ()=>{
  document.getElementById('frm').style.display='none';
  document.getElementById('progreso').style.display='block';
  run('init');
});
<?php
// Solo unseteamos restore_zip aquí.
// restore_allow_index debe permanecer hasta que restaurar_proc.php lo lea y lo limpie.
unset($_SESSION['restore_zip']);
// La siguiente línea se mantiene, pero la variable $allow ya no se usa directamente en el JS de esta página.
// $allow = !empty($_SESSION['restore_allow_index']);
// ¡LA SIGUIENTE LÍNEA ES LA QUE SE ELIMINA!
// unset($_SESSION['restore_allow_index']);
endif;
// Es importante cerrar la sesión después de haber usado sus variables
// para evitar bloqueos en las peticiones AJAX de restaurar_proc.php
session_write_close();
?>

let total=0, proc=0;

function log(m){
  const d=new Date().toLocaleTimeString();
  const el=document.getElementById('log');
  el.innerHTML += `[${d}] ${m}<br>`;
  el.scrollTop=1e9;
}
function pct(){
  const p = total? Math.round(proc*100/total) : 100;
  const b=document.querySelector('#barra div');
  b.style.width=p+'%'; b.textContent=p+'%';
}
function post(accion, payload={}){
  const body=new URLSearchParams(Object.assign({accion, zip: ZIP, csrf: CSRF}, payload));
  return fetch(PROC_URL,{method:'POST', body}).then(r=>r.json());
}
function run(accion, intento=0){
  post(accion).then(d=>{
    if(d.error){
      log('❌ '+d.error);
      mostrarBotonIndex(true);
      return;
    }
    if(accion==='init'){
      total=d.total||0; proc=0; pct();
      if(total===0){ log('ZIP vacío'); mostrarBotonIndex(true); return; }
    }
    if(accion==='chunk'){
      proc=d.procesados||proc; pct();
      if(Array.isArray(d.logs)) d.logs.forEach(m=>log(m));
    }
    if(d.finalizado){
      log('✅ Restauración completa');
      mostrarBotonIndex(false);
      return;
    }
    run('chunk');
  }).catch(e=>{
    if(intento<5){
      setTimeout(()=>run(accion,intento+1), 1000*(intento+1));
    }else{
      log('❌ Error de red repetido');
      mostrarBotonIndex(true);
    }
  });
}

function mostrarBotonIndex(esError){
  const cont = document.getElementById('progreso');
  if (document.getElementById('btnVolver')) return; // evita duplicados
  const btn = document.createElement('button');
  btn.id = 'btnVolver';
  btn.textContent = 'Ir al Panel';
  btn.className = esError ? 'btn-warn' : 'btn-ok';
  btn.style.marginTop = '10px';
  btn.onclick = () => window.location.href = 'index.php';
  cont.appendChild(btn);
}
</script>