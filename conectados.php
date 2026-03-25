<?php
// Ya nos aseguramos de que el usuario tiene sesión válida
require_once __DIR__ . '/verificar_sesion.php';
// Ya no necesitamos funciones.php ni dependencias raras
// Toda la lógica y datos los consume por JS desde chat_api.php
$usuarioActual = strtolower($_SESSION['usuario']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>💬 Chat</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      --color-conectado: #4caf50;
      --color-inactivo: #ffc107;
      --color-fondo: #f4f4f4;
      --color-blanco: #fff;
      --color-texto: #333;
      --color-borde: #ddd;
      --sombra: 0 2px 8px rgba(0,0,0,0.1);
      --color-notificacion: #f44336;
      --color-video: #1a73e8; 
    }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      margin: 0; padding: 1rem; background-color: var(--color-fondo);
      color: var(--color-texto); display: grid; grid-template-columns: 300px 1fr;
      gap: 1.5rem; height: 100vh; box-sizing: border-box;
    }
    .panel {
      background: var(--color-blanco); border-radius: 8px; padding: 1.5rem;
      box-shadow: var(--sombra); display: flex; flex-direction: column; overflow-y: auto;
    }
    h2, h3 { margin-top: 0; }
    #lista-usuarios .usuario {
      display: flex; justify-content: space-between; align-items: center;
      padding: 0.8rem; margin-bottom: 0.5rem; border-radius: 6px;
      border-left: 5px solid var(--color-conectado); cursor: pointer;
      transition: background-color 0.2s, border-left-color 0.2s;
    }
    #lista-usuarios .usuario.selected { background-color: #e8f0fe; border-left-color: var(--color-video); }
    #lista-usuarios .usuario:hover { background-color: #f0f0f0; }
    #lista-usuarios .usuario.inactivo { border-left-color: var(--color-inactivo); }
    .notificacion-badge {
        background-color: var(--color-notificacion); color: var(--color-blanco);
        border-radius: 50%; padding: 2px 8px; font-size: 0.8em;
        font-weight: bold; min-width: 10px; text-align: center; line-height: 1.5;
    }
    #lista-usuarios .usuario .info-usuario { display: flex; flex-direction: column; }
    #lista-usuarios .usuario strong { font-size: 1.1em; text-transform: capitalize; }
    #lista-usuarios .usuario small { display: block; color: #666; font-size: 0.85em; }
    #chat-container { display: flex; flex-direction: column; height: 100%; box-sizing: border-box; }
    #chat-header { margin-bottom: 1rem; text-transform: capitalize; }
    #chat-historial {
      flex-grow: 1; overflow-y: auto; padding: 1rem; border: 1px solid var(--color-borde);
      border-radius: 4px; background: #f9f9f9; margin-bottom: 1rem;
    }
    .mensaje {
      padding: 0.5rem 0.8rem; border-radius: 12px; margin-bottom: 0.7rem;
      max-width: 70%; word-wrap: break-word; display: flex; flex-direction: column;
    }
    .mensaje.enviado { background-color: #dcf8c6; align-self: flex-end; }
    .mensaje.recibido { background-color: var(--color-blanco); border: 1px solid var(--color-borde); align-self: flex-start; }
    .mensaje-autor { font-weight: bold; font-size: 0.9em; margin-bottom: 0.2rem; text-transform: capitalize; }
    .mensaje-texto { text-align: left; }
    .mensaje-hora { font-size: 0.75em; color: #999; margin-top: 0.2rem; text-align: right; }
    #chat-input-form { display: flex; gap: 0.5rem; }
    #mensaje-texto { flex-grow: 1; padding: 0.8rem; border-radius: 20px; border: 1px solid var(--color-borde); }
    
    #enviar-btn, #video-btn {
        padding: 0.8rem 1.2rem;
        border-radius: 20px;
        border: none;
        color: white;
        cursor: pointer;
        transition: background-color 0.2s, opacity 0.2s;
    }
    #enviar-btn { background-color: var(--color-conectado); }
    #video-btn { 
        background-color: var(--color-video);
        font-size: 1.1rem; 
        padding: 0.8rem; 
        line-height: 1;
    }
    #enviar-btn:disabled, #video-btn:disabled {
        background-color: #ccc;
        cursor: not-allowed;
        opacity: 0.7;
    }

    @media (max-width: 768px) { body { grid-template-columns: 1fr; height: auto; } }
  </style>
</head>
<body>
<aside class="panel">
  <h2>🧑‍💻 Usuarios (<span id="contador-usuarios">0</span>)</h2>
  <div id="lista-usuarios"></div>
</aside>
<main class="panel" id="chat-container">
  <div id="chat-header">
    <h3>💬 Chat con: <span id="nombre-chat-actual">Nadie</span></h3>
  </div>
  <div id="chat-historial">
     <p style="text-align:center; color:gray;">Selecciona un usuario de la lista para comenzar a chatear.</p>
  </div>
  
  <form id="chat-input-form" onsubmit="enviarMensaje(event)">
    <input type="text" id="mensaje-texto" placeholder="Escribe tu mensaje..." autocomplete="off" disabled>
    <button id="video-btn" type="button" title="Iniciar videollamada" disabled>📹</button>
    <button id="enviar-btn" type="submit" disabled>Enviar</button>
  </form>

</main>
<audio id="sonido-mensaje" src="notificacion.mp3" preload="auto"></audio>

<script>
const miUsuario = '<?= htmlspecialchars($usuarioActual, ENT_QUOTES, 'UTF-8') ?>';
let usuarioSeleccionado = null;
let estaActivaLaVentana = true;
let mensajesMostrados = new Set();
let ultimoTotalNoLeidos = 0;
let intervaloActualizacion;

const listaUsuariosEl = document.getElementById('lista-usuarios');
const chatHistorialEl = document.getElementById('chat-historial');
const mensajeTextoEl = document.getElementById('mensaje-texto');
const enviarBtnEl = document.getElementById('enviar-btn');
const nombreChatActualEl = document.getElementById('nombre-chat-actual');
const contadorUsuariosEl = document.getElementById('contador-usuarios');
const sonidoMensajeEl = document.getElementById('sonido-mensaje');
const videoBtnEl = document.getElementById('video-btn');

window.onfocus = () => {
    estaActivaLaVentana = true;
    document.title = "💬 Chat";
    if (usuarioSeleccionado) {
        cargarHistorial(usuarioSeleccionado, false); 
    }
};
window.onblur = () => { estaActivaLaVentana = false; };

function escapeHTML(str) {
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}

function agregarMensajeAlDOM(msg) {
    if (!msg.id || mensajesMostrados.has(msg.id)) return false;
    mensajesMostrados.add(msg.id);

    const clase = msg.de.toLowerCase() === miUsuario ? 'enviado' : 'recibido';
    const msgDiv = document.createElement('div');
    msgDiv.className = `mensaje ${clase}`;
    msgDiv.id = msg.id;
    msgDiv.innerHTML = `
        <div class="mensaje-autor">${escapeHTML(msg.de)}</div>
        <div class="mensaje-texto">${escapeHTML(msg.texto)}</div>
        <div class="mensaje-hora">${new Date(msg.timestamp * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
    `;
    chatHistorialEl.appendChild(msgDiv);
    return true;
}

async function seleccionarUsuario(username) {
    if (usuarioSeleccionado === username) return;

    usuarioSeleccionado = username;
    nombreChatActualEl.textContent = username;
    document.querySelectorAll('#lista-usuarios .usuario').forEach(u => {
        u.classList.toggle('selected', u.getAttribute('data-username') === username);
    });
    chatHistorialEl.innerHTML = '<p style="text-align:center; color:gray;">Cargando mensajes...</p>';
    mensajeTextoEl.disabled = false;
    enviarBtnEl.disabled = false;
    videoBtnEl.disabled = false;
    
    mensajeTextoEl.focus();
    mensajesMostrados.clear();
    
    await cargarHistorial(username, true); 
    await actualizarEstadoGeneral(); 
}

async function cargarHistorial(para, hacerScroll) {
    if (!para) return;
    const estabaAlFinal = chatHistorialEl.scrollTop + chatHistorialEl.clientHeight >= chatHistorialEl.scrollHeight - 20;

    try {
        const response = await fetch('chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'historial', para: para })
        });
        const data = await response.json();

        if (data.ok && data.mensajes) {
            if (mensajesMostrados.size === 0) {
                 chatHistorialEl.innerHTML = '';
            }

            let seAgregoMensaje = false;
            data.mensajes.forEach(msg => {
                if (agregarMensajeAlDOM(msg)) {
                    seAgregoMensaje = true;
                }
            });
            
            if (chatHistorialEl.innerHTML === '' && data.mensajes.length === 0) {
                 chatHistorialEl.innerHTML = '<p style="text-align:center; color:gray;">No hay mensajes. ¡Sé el primero en saludar!</p>';
            }

            if (hacerScroll || (seAgregoMensaje && estabaAlFinal)) {
                chatHistorialEl.scrollTop = chatHistorialEl.scrollHeight;
            }
        } else {
             throw new Error(data.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error al cargar historial:', error);
        chatHistorialEl.innerHTML = `<p style="text-align:center; color:red;">Error al cargar el historial.</p>`;
    }
}

async function enviarMensaje(event) {
    event.preventDefault();
    const texto = mensajeTextoEl.value.trim();
    if (!usuarioSeleccionado || !texto) return;

    const tempId = `temp_${Date.now()}`;
    agregarMensajeAlDOM({ id: tempId, de: miUsuario, texto: texto, timestamp: Math.floor(Date.now() / 1000) });
    chatHistorialEl.scrollTop = chatHistorialEl.scrollHeight;
    
    const mensajeOriginal = mensajeTextoEl.value;
    mensajeTextoEl.value = '';

    try {
        const response = await fetch('chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'enviar', para: usuarioSeleccionado, texto })
        });
        const data = await response.json();
        document.getElementById(tempId)?.remove();
        if(data.ok && data.mensaje) {
            agregarMensajeAlDOM(data.mensaje);
        } else {
            throw new Error(data.error || 'Fallo al enviar');
        }
    } catch (error) {
        console.error('Error al enviar mensaje:', error);
        alert('No se pudo enviar el mensaje.');
        document.getElementById(tempId)?.remove();
        mensajeTextoEl.value = mensajeOriginal; 
    }
}

function actualizarListaUsuarios(usuarios) {
    const usuariosFiltrados = Object.values(usuarios).filter(u => u.username !== miUsuario);
    contadorUsuariosEl.textContent = usuariosFiltrados.length;
    
    let totalNoLeidosActual = 0;
    const usuarioSeleccionadoActual = usuarioSeleccionado;
    listaUsuariosEl.innerHTML = ''; 

    if (usuariosFiltrados.length === 0) {
        listaUsuariosEl.innerHTML = '<p style="color:gray;">No hay otros usuarios conectados.</p>';
    } else {
        usuariosFiltrados.sort((a,b) => a.username.localeCompare(b.username)).forEach(info => {
            const username = info.username;
            const claseInactivo = info.estado.includes('Inactivo') ? 'inactivo' : '';
            const claseSeleccionado = username === usuarioSeleccionadoActual ? 'selected' : '';
            const usuarioDiv = document.createElement('div');
            usuarioDiv.className = `usuario ${claseInactivo} ${claseSeleccionado}`;
            usuarioDiv.setAttribute('data-username', username);

            let badgeHTML = '';
            if (info.no_leidos > 0) {
                badgeHTML = `<span class="notificacion-badge">${info.no_leidos}</span>`;
                totalNoLeidosActual += info.no_leidos;
            }
            
            usuarioDiv.innerHTML = `
                <div class="info-usuario">
                    <strong>${escapeHTML(username)}</strong>
                    <small>${escapeHTML(info.nombre)} ${escapeHTML(info.apellido)}</small>
                    <small>${info.estado}</small>
                </div>
                ${badgeHTML}
            `;
            usuarioDiv.addEventListener('click', () => seleccionarUsuario(username));
            listaUsuariosEl.appendChild(usuarioDiv);
        });
    }

    if (totalNoLeidosActual > ultimoTotalNoLeidos && estaActivaLaVentana) {
        sonidoMensajeEl.play().catch(e => {});
    }
    if (!estaActivaLaVentana && totalNoLeidosActual > 0) {
        document.title = `(${totalNoLeidosActual}) 💬 Chat`;
    }
    ultimoTotalNoLeidos = totalNoLeidosActual;
}

async function actualizarEstadoGeneral() {
    try {
        const response = await fetch('chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'obtener_usuarios' })
        });
        const data = await response.json();
        
        if (data.ok) {
            actualizarListaUsuarios(data.usuarios);
            
            if (usuarioSeleccionado && estaActivaLaVentana) {
                await cargarHistorial(usuarioSeleccionado, false);
            }
        }
    } catch (error) {
        console.error("Error en la actualización general:", error);
    }
}

async function iniciarVideollamada() {
    if (!usuarioSeleccionado) {
        alert('Por favor, selecciona un usuario para llamar.');
        return;
    }

    try {
        const response = await fetch('chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                accion: 'solicitar_llamada',
                para: usuarioSeleccionado
            })
        });

        const data = await response.json();
        if (!data.ok) {
            alert(data.error || 'Error al enviar la solicitud de llamada');
            return;
        }

        const usuarios = [miUsuario, usuarioSeleccionado].sort();
        const sala = `Creawebes_${usuarios[0]}_con_${usuarios[1]}`;
        window.open(`https://meet.jit.si/${sala}`, '_blank');

    } catch (error) {
        console.error('Error al iniciar la videollamada:', error);
        alert('Error al iniciar la videollamada.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    actualizarEstadoGeneral(); 
    intervaloActualizacion = setInterval(actualizarEstadoGeneral, 3000);
    videoBtnEl.addEventListener('click', iniciarVideollamada);
});
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
