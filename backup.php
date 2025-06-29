<?php
// backup.php (Interfaz visual con barra de carga)
session_start();
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Crear copia de seguridad – Panel Creawebes</title>
<link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
<style>
 body{font-family:'Lato',sans-serif;background:#f5f8ff;margin:0;padding:2rem}
 h1{margin:0 0 1rem;color:#3949ab}
 button{background:#3949ab;color:#fff;border:none;padding:.6rem 1.2rem;border-radius:6px;font-weight:bold;cursor:pointer;margin-right:.5rem}
 #progresoBox{margin-top:2rem}
 #barra{height:22px;background:#d0d8ff;border-radius:12px;overflow:hidden}
 #barra div{height:100%;width:0;background:#3949ab;color:#fff;text-align:center;line-height:22px;font-size:.8rem}
 #log{background:#fff;border:1px solid #ddd;padding:1rem;margin-top:1rem;height:140px;overflow:auto;font-size:.85rem}
</style>
</head>
<body>
<h1>🧩 Crear copia de seguridad</h1>

<button onclick="iniciar()">Crear copia ahora</button>
<div id="progresoBox" style="display:none">
  <div id="barra"><div>0%</div></div>
  <div id="log"></div>
</div>

<script>
let total = 0, procesados = 0;
function iniciar(){
  document.querySelector('button').disabled = true;
  document.getElementById('progresoBox').style.display='block';
  log('⏳ Iniciando creación de copia de seguridad...');
  fetchPaso('init');
}

function fetchPaso(accion){
  const body = new URLSearchParams({accion});
  fetch('backup_proc.php',{method:'POST',body})
    .then(r=>r.json())
    .then(data=>{
      if(data.error){ log('❌ '+data.error); return; }
      if(accion==='init'){ total = data.total; procesados = 0; }
      if(accion==='chunk'){ procesados = data.procesados; }
      actualizarBarra();
      if(data.finalizado){
        log('✅ Copia creada: '+data.nombre);
        log('📄 Informe: '+data.informe);
        document.getElementById('log').innerHTML += '<br><button onclick="window.location.href=\'index.php\'">🔙 Volver al inicio</button>';
      } else {
        fetchPaso('chunk');
      }
    })
    .catch(e=>log('❌ '+e));
}

function actualizarBarra(){
  const pct = Math.round(procesados * 100 / total);
  const bar = document.querySelector('#barra div');
  bar.style.width = pct+'%';
  bar.textContent = pct+'%';
}

function log(m){
  const d = new Date().toLocaleTimeString();
  document.getElementById('log').innerHTML += '['+d+'] '+m+'<br>';
  document.getElementById('log').scrollTop = 999999;
}
</script>
</body>
</html>
