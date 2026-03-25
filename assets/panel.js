// ==========================================
// panel.js - Lógica principal del explorador
// ==========================================

const panelConfig = JSON.parse(document.getElementById('panel-config').textContent || '{}');
const esAdmin = panelConfig.esAdmin;
const miCarpeta = panelConfig.miCarpeta;
const todosLosUsuarios = panelConfig.todosLosUsuarios;
const miUsuarioDesdeIndex = panelConfig.miUsuarioDesdeIndex;

    // ==================================================================
    //  BLOQUE DE JAVASCRIPT COMPLETO Y CORREGIDO (VERSIÓN DEFINITIVA)
    // ==================================================================

    // --- Variables Globales ---
    let current = '';
    let seleccionados = new Set();
    let sistemaVisible;
    let menuCloseTimer; // Temporizador para el cierre del menú por hover

    // --- Función para ventana de conectados (integrada aquí) ---
    function abrirVentanaConectados() {
        const ventana = window.open('conectados.php', 'VentanaConectados', 'width=400,height=600,resizable=yes,scrollbars=yes');
        if (!ventana) alert('❌ Tu navegador bloqueó la ventana emergente.');
    }
    
    // =========================================================
    // ✅ INICIO: JS PARA NUEVOS MENÚS DESPLEGABLES
    // =========================================================
    function toggleHerramientas() {
        const menu = document.getElementById("herramientasContenido");
        menu.style.display = (menu.style.display === "block") ? "none" : "block";
    }
    
    document.addEventListener("click", function(event) {
        // Cierra menú Ordenar si se hace clic fuera
        if (!event.target.closest('#menuOrden') && !event.target.closest('button[onclick="toggleMenuOrden()"]')) {
            const menuOrden = document.getElementById("menuOrden");
            if (menuOrden && menuOrden.style.display === "block") {
                menuOrden.style.display = "none";
            }
        }
        
        // Cierra menú Herramientas si se hace clic fuera
        if (!event.target.closest('.herramientas-dropdown')) {
            const menuHerramientas = document.getElementById("herramientasContenido");
            if (menuHerramientas && menuHerramientas.style.display === 'block') {
                menuHerramientas.style.display = 'none';
            }
        }
    });
    // =========================================================
    // ✅ FIN: JS PARA NUEVOS MENÚS DESPLEGABLES
    // =========================================================


    // --- Lógica Principal al Cargar la Página ---
    document.addEventListener('DOMContentLoaded', () => {
        // Restaurar visibilidad de archivos de sistema
        if (sessionStorage.getItem('sistemaVisible') === 'true') {
            document.querySelectorAll('.archivo-sistema').forEach(e => e.style.display = 'flex');
            sistemaVisible = true;
            const boton = document.getElementById('botonSistema');
            if (boton) boton.innerHTML = '🔓 Ocultar archivos del sistema';
        } else {
            sistemaVisible = false;
        }

        const exploradorLista = document.querySelector('ul.explorador');
        const menu = document.getElementById('menu');
        if (!exploradorLista) return;

        // --- LÓGICA DE EVENTOS CENTRALIZADA ---

        const closeMenu = () => {
            if (menu) menu.style.display = 'none';
        };

        // 1. ABRIR MENÚ CONTEXTUAL (Click Derecho)
        exploradorLista.addEventListener('contextmenu', e => {
            const el = e.target.closest('.archivo, .carpeta');
            if (!el) return;

            e.preventDefault();
            clearTimeout(menuCloseTimer); // Cancela cualquier cierre por hover pendiente

            // Lógica de selección
            const nombre = el.dataset.nombre;
            if (!el.classList.contains('seleccionado')) {
                document.querySelectorAll('li.seleccionado').forEach(sel => sel.classList.remove('seleccionado'));
                seleccionados.clear();
                el.classList.add('seleccionado');
                seleccionados.add(nombre);
            }
            current = nombre;

            // ### MODIFICADO ### Lógica para el botón de compartir y construcción del menú
            const isDir = el.classList.contains('carpeta');
            const rutaItem = el.dataset.nombre;
            const esCarpetaDeUsuario = isDir && rutaItem.startsWith('usuarios/');
            const dueñoCarpeta = esCarpetaDeUsuario ? rutaItem.split('/')[1] : null;
            const puedeCompartir = esAdmin || (miCarpeta && miCarpeta === dueñoCarpeta);
            
            menu.innerHTML = '';
            if (seleccionados.size > 1) {
                menu.innerHTML += '<button onclick="eliminarSeleccionados()">🗑️ Eliminar seleccionados</button>';
                menu.innerHTML += '<button onclick="moverSeleccionados()">📂 Mover seleccionados</button>';
                menu.innerHTML += '<button onclick="mostrarModalComprimir()">📦 Comprimir seleccionados</button>';
                if (panelConfig.enPapelera) {
                menu.innerHTML += '<button onclick="restaurarSeleccionados()">♻️ Restaurar seleccionados</button>';
                }
            } else {
                 if (!isDir && (nombre.endsWith('.php') || nombre.endsWith('.html') || nombre.endsWith('.js') || nombre.endsWith('.css') || nombre.endsWith('.txt'))) {
                    menu.innerHTML += '<button onclick="editarArchivo()">✍️ Editar código</button>';
                } else if (!isDir) {
                    menu.innerHTML += '<button onclick="alert(\'Solo se pueden editar archivos PHP, HTML, JS, CSS o TXT.\')">✍️ Editar</button>';
                }

                // ### NUEVO ### Añadir botón de compartir
                if (esCarpetaDeUsuario && puedeCompartir) {
                    menu.innerHTML += '<button onclick="mostrarModalCompartir()">🔗 Compartir / Gestionar</button>';
                }

                menu.innerHTML += '<button onclick="renombrarItem()">✏️ Renombrar</button>';
                menu.innerHTML += '<button onclick="eliminarItem()">🗑️ Eliminar</button>';
                menu.innerHTML += '<button onclick="duplicarItem()">📄 Duplicar</button>';
                menu.innerHTML += '<button onclick="descargarArchivo()">📥 Descargar</button>';
                menu.innerHTML += '<button onclick="moverArchivoIndividual()">📂 Mover</button>';
                menu.innerHTML += '<button onclick="mostrarModalComprimir()">📦 Comprimir</button>';
                if (nombre.endsWith('.zip')) {
                    menu.innerHTML += '<button onclick="mostrarModalDescomprimir()">📂 Descomprimir</button>';
                }
                if (panelConfig.enPapelera) {
                menu.innerHTML += '<button onclick="restaurarItem()">♻️ Restaurar</button>';
                }
            }
            
            // Posicionar y mostrar menú
            menu.style.display = 'block';
            let top = e.pageY;
            let left = e.pageX;
            if (top + menu.offsetHeight > window.innerHeight + window.scrollY) top = e.pageY - menu.offsetHeight;
            if (left + menu.offsetWidth > window.innerWidth + window.scrollX) left = e.pageX - menu.offsetWidth;
            menu.style.left = left + 'px';
            menu.style.top = top + 'px';
            
            const startCloseTimer = () => { menuCloseTimer = setTimeout(closeMenu, 400); };
            const cancelCloseTimer = () => { clearTimeout(menuCloseTimer); };
            el.onmouseleave = startCloseTimer;
            el.onmouseenter = cancelCloseTimer;
            menu.onmouseleave = startCloseTimer;
            menu.onmouseenter = cancelCloseTimer;
        });

        // 2. GESTIONAR CLICS IZQUIERDOS (Selección y Navegación)
        exploradorLista.addEventListener('click', e => {
            const el = e.target.closest('.archivo, .carpeta');
            if (!el) return;

            if (e.ctrlKey || e.metaKey || e.target.tagName !== 'A') {
                e.preventDefault(); 
                seleccionarItem(el, e);
            }
        });

        // 3. CERRAR MENÚ CONTEXTUAL (Click en cualquier otro lugar)
        document.addEventListener('click', e => {
            if (menu.style.display === 'block' && !e.target.closest('#menu')) {
                closeMenu();
            }
        });

        // Listeners para los formularios de los modales
        const formComprimir = document.getElementById('formComprimir');
        if(formComprimir) formComprimir.onsubmit = function(e) { e.preventDefault(); const fd=new FormData(this); fetch('comprimir.php',{method:'POST',body:fd}).then(r=>r.text()).then(res=>{alert(res);location.reload();}).catch(err=>{alert('Error: '+err);}); };
        
        const formDescomprimir = document.getElementById('formDescomprimir');
        if(formDescomprimir) formDescomprimir.onsubmit = function(e) { e.preventDefault(); const fd=new FormData(this); fetch('descomprimir.php',{method:'POST',body:fd}).then(r=>r.text()).then(res=>{alert(res);location.reload();}).catch(err=>{alert('Error: '+err);}); };
    });

    // --- DEFINICIÓN DE FUNCIONES AUXILIARES ---

    function seleccionarItem(el, evento) {
        const nombre = el.dataset.nombre;
        if (evento.ctrlKey || evento.metaKey) {
            el.classList.toggle('seleccionado');
            seleccionados.has(nombre) ? seleccionados.delete(nombre) : seleccionados.add(nombre);
        } else {
            if (el.classList.contains('seleccionado') && seleccionados.size === 1) return;
            document.querySelectorAll('li.seleccionado').forEach(e => e.classList.remove('seleccionado'));
            seleccionados.clear();
            el.classList.add('seleccionado');
            seleccionados.add(nombre);
        }
    }

    function enviarFormularioAccion(accion, valorAdicional = {}) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?carpeta='+ panelConfig.carpetaRelativaUrlEncoded +'';
        let inputs = `<input type="hidden" name="accion" value="${accion}"><input type="hidden" name="archivo" value="${current}">`;
        for (const key in valorAdicional) { inputs += `<input type="hidden" name="${key}" value="${valorAdicional[key]}">`; }
        form.innerHTML = inputs;
        document.body.appendChild(form);
        form.submit();
    }
    
    function renombrarItem() { const n = prompt('Nuevo nombre para ' + current.split('/').pop() + ':'); if (n) enviarFormularioAccion('renombrar', { 'nuevo_nombre': n }); }
    function eliminarItem() { if (confirm('¿Estás seguro de eliminar ' + current.split('/').pop() + '?')) enviarFormularioAccion('eliminar'); }
    function duplicarItem() { enviarFormularioAccion('duplicar'); }
    // Modificar editarArchivo para incluir la lógica de override
    function editarArchivo() {
        const archivoParaEditar = current;
        // La comparación con ROOT_DIR + /index.php ahora se hace en PHP en el script de editor.php
        // Si el archivo es root/index.php, editor.php redirigirá al formulario de confirmación.
        // Si ya hay un override activo, el script de editor.php lo detectará y abrirá el editor.
        window.open('editor.php?archivo=' + encodeURIComponent(archivoParaEditar), '_blank');
    }
    function descargarArchivo() { window.location.href = 'download.php?archivo=' + encodeURIComponent(current); }
    
    function moverArchivoIndividual() {
        document.getElementById('accionMover').value = 'mover';
        document.getElementById('moverArchivo').value = current;
        document.getElementById('moverArchivosJson').value = '';
        const _sis1 = (typeof sistemaVisible !== 'undefined' && sistemaVisible) ? '&sistema=1' : '';
        fetch('index.php?listar=1&actual='+ panelConfig.carpetaRelativaUrlEncoded +'' + _sis1)
            .then(r => r.json())
            .then(data => {
                const sel = document.getElementById('selectDestino');
                sel.innerHTML = '<option value="">📁 Inicio</option>';
                data.forEach(c => { if(c) { const op = document.createElement('option'); op.value = c; op.textContent = '📁 ' + c; sel.appendChild(op); } });
                document.getElementById('modalMover').style.display = 'flex';
            })
            .catch(err => console.error("Error en fetch: " + err.message));
    }

    function crearCarpeta() {
        const n = prompt('Nombre de nueva carpeta:');
        if (!n || n.trim() === '') return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?carpeta='+ panelConfig.carpetaRelativaUrlEncoded +'';
        form.innerHTML = `<input type="hidden" name="accion" value="crear_carpeta"><input type="hidden" name="nueva_carpeta" value="${n}">`;
        document.body.appendChild(form);
        form.submit();
    }

    function mostrarSubida() { document.getElementById('subida').style.display = document.getElementById('subida').style.display === 'none' ? 'block' : 'none'; }
    
    function eliminarSeleccionados() {
        if (seleccionados.size === 0 || !confirm("¿Eliminar los " + seleccionados.size + " elementos seleccionados?")) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?carpeta='+ panelConfig.carpetaRelativaUrlEncoded +'';
        form.innerHTML = `<input type="hidden" name="accion" value="eliminar_multiple"><input type="hidden" name="archivos_json" value='${JSON.stringify(Array.from(seleccionados))}'>`;
        document.body.appendChild(form);
        form.submit();
    }

    function moverSeleccionados() {
        if (seleccionados.size === 0) return;
        const _sis2 = (typeof sistemaVisible !== 'undefined' && sistemaVisible) ? '&sistema=1' : '';
        fetch('index.php?listar=1&actual='+ panelConfig.carpetaRelativaUrlEncoded +'' + _sis2).then(r => r.json()).then(data => {
            const sel = document.getElementById('selectDestino');
            sel.innerHTML = '<option value="">📁 Inicio</option>';
            data.forEach(c => { if(c) { const op = document.createElement('option'); op.value = c; op.textContent = '📁 ' + c; sel.appendChild(op); } });
            document.getElementById('accionMover').value = 'mover_multiple';
            document.getElementById('moverArchivosJson').value = JSON.stringify(Array.from(seleccionados));
            document.getElementById('modalMover').style.display = 'flex';
        });
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') { document.querySelectorAll('.modal').forEach(m => m.style.display = 'none'); } });
    function toggleMenuOrden() { const menu = document.getElementById("menuOrden"); menu.style.display = (menu.style.display === "none" || !menu.style.display) ? "block" : "none"; }
    
    const buscador = document.getElementById('buscador');
    if (buscador) buscador.addEventListener('input', function() {
        const valor = this.value.trim(); const resDiv = document.getElementById('resultadosBusqueda');
        if (valor.length < 2) { resDiv.innerHTML = ''; return; }
        const mostrarSistema = (typeof sistemaVisible !== 'undefined' && sistemaVisible) ? '&sistema=1' : '';
        fetch('buscar.php?q=' + encodeURIComponent(valor) + mostrarSistema).then(res => res.json()).then(datos => {
            resDiv.innerHTML = ''; // Limpiar resultados anteriores
            if (!datos || !datos.length) { resDiv.innerHTML = '<p style="padding: 0 1rem;">No se encontraron resultados.</p>'; return; }
            resDiv.innerHTML = '<div style="margin-bottom: 1rem;">' + datos.map(item => `<div style="padding:4px;"><a href="${item.tipo==='carpeta'?'index.php?carpeta='+encodeURIComponent(item.ruta):item.ruta}" style="text-decoration: none; color: var(--color-primario);">${item.tipo==='carpeta'?'📁':'📄'} ${item.ruta}</a></div>`).join('') + '</div>';
        });
    });
    
    function restaurarItem() { if (confirm("¿Restaurar a ubicación original?")) enviarFormularioAccion('restaurar'); }
    function restaurarSeleccionados() {
        if (seleccionados.size === 0 || !confirm("¿Restaurar " + seleccionados.size + " elementos?")) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?carpeta='+ panelConfig.carpetaRelativaUrlEncoded +'';
        form.innerHTML = `<input type="hidden" name="accion" value="restaurar_multiple"><input type="hidden" name="archivos_json" value='${JSON.stringify(Array.from(seleccionados))}'>`;
        document.body.appendChild(form);
        form.submit();
    }

    function mostrarModalComprimir() {
        if (seleccionados.size === 0) { alert('Selecciona al menos un elemento.'); return; }
        const _sis3 = (typeof sistemaVisible !== 'undefined' && sistemaVisible) ? '&sistema=1' : '';
        fetch('index.php?listar=1&actual='+ panelConfig.carpetaRelativaUrlEncoded +'' + _sis3).then(r => r.json()).then(data => {
            const sel = document.querySelector('#modalComprimir select[name="destino"]');
            sel.innerHTML = '<option value="' + panelConfig.carpetaRelativaHtml + '">📁 (Carpeta actual)</option><option value="">📁 Inicio (Raíz)</option>';
            data.forEach(c => { if (c.trim() !== '' && c.trim() !== '' + panelConfig.carpetaRelativaSlashes + '') { const op = document.createElement('option'); op.value = c; op.textContent = '📁 ' + c; sel.appendChild(op); } });
            document.querySelector('#modalComprimir input[name="archivos_json"]').value = JSON.stringify(Array.from(seleccionados));
            document.getElementById('modalComprimir').style.display = 'flex';
        });
    }

    function mostrarModalDescomprimir() {
        if (!current.endsWith('.zip')) { alert('Solo se pueden descomprimir archivos .zip'); return; }

        const _sis4 = (typeof sistemaVisible !== 'undefined' && sistemaVisible) ? '&sistema=1' : '';
        fetch('index.php?listar=1&actual='+ panelConfig.carpetaRelativaUrlEncoded +'' + _sis4).then(r => r.json()).then(data => {
            const sel = document.querySelector('#modalDescomprimir select[name="destino"]');
            
            sel.innerHTML = '';

            const optActual = document.createElement('option');
            optActual.value = "' + panelConfig.carpetaRelativaHtml + '";
            optActual.textContent = "📁 (Carpeta actual)";
            sel.appendChild(optActual);
            
            const optInicio = document.createElement('option');
            optInicio.value = ""; 
            optInicio.textContent = "📁 Inicio (Raíz)";
            sel.appendChild(optInicio);

            data.forEach(c => { 
                if (c.trim() !== '') { 
                    const op = document.createElement('option'); 
                    op.value = c; 
                    op.textContent = '📁 ' + c; 
                    sel.appendChild(op); 
                } 
            });
            
            document.querySelector('#modalDescomprimir input[name="archivo"]').value = current;
            document.getElementById('modalDescomprimir').style.display = 'flex';
        });
    }
    
    function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }
    function alternarSistema() { if (sistemaVisible) ocultarSistema(); else { document.getElementById('modalSistema').style.display = 'flex'; document.getElementById('claveSistema').focus(); } }
    
    function verSistema() {
        const clave = document.getElementById('claveSistema').value;
        if (clave === panelConfig.sistemaPassword) {
            document.querySelectorAll('.archivo-sistema').forEach(e => e.style.display = 'flex');
            sistemaVisible = true;
            cerrarModal('modalSistema');
            const boton = document.getElementById('botonSistema');
            if(boton) boton.innerHTML = '🔓 Ocultar archivos del sistema';
            sessionStorage.setItem('sistemaVisible', 'true');
        } else {
            alert('❌ Contraseña incorrecta.');
        }
    }

    function ocultarSistema() {
        document.querySelectorAll('.archivo-sistema').forEach(e => e.style.display = 'none');
        sistemaVisible = false;
        const boton = document.getElementById('botonSistema');
        if(boton) boton.innerHTML = '🔐 Archivo de sistema';
        sessionStorage.removeItem('sistemaVisible');
    }

    // ### NUEVO ### Lógica de JavaScript para la funcionalidad de Compartir
    /**
     * Muestra el modal para compartir, cargando los datos actuales.
     */
    async function mostrarModalCompartir() {
        const rutaCarpeta = current; // 'current' es la variable global con el item seleccionado
        if (!rutaCarpeta) return;

        document.getElementById('nombreCarpetaCompartir').textContent = rutaCarpeta.split('/').pop();
        document.getElementById('rutaCarpetaInput').value = rutaCarpeta;
        const listaDiv = document.getElementById('listaUsuariosCompartir');
        listaDiv.innerHTML = '<p>Cargando...</p>';
        document.getElementById('modalCompartir').style.display = 'flex';

        try {
            // 1. Pedimos al backend con quién está compartida actualmente la carpeta
            const response = await fetch('gestor_compartir.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ accion: 'get_info', ruta: rutaCarpeta })
            });
            const data = await response.json();

            if (!response.ok || !data.ok) {
                throw new Error(data.mensaje || 'Error del servidor.');
            }

            const compartidoCon = new Set(data.compartidoCon || []);
            listaDiv.innerHTML = ''; // Limpiamos el 'Cargando...'

            // 2. Construimos la lista de checkboxes con todos los usuarios
            const dueñoCarpeta = rutaCarpeta.split('/')[1];
            todosLosUsuarios.forEach(usuario => {
                // No mostramos al dueño de la carpeta en la lista para compartir consigo mismo
                if (usuario === dueñoCarpeta) return;

                const isChecked = compartidoCon.has(usuario);
                const label = document.createElement('label');
                label.style.display = 'block';
                label.style.marginBottom = '8px';
                label.innerHTML = `
                    <input type="checkbox" name="usuarios_compartir[]" value="${usuario}" ${isChecked ? 'checked' : ''}>
                    👤 ${usuario.replace(/_/g, ' ')}
                `;
                listaDiv.appendChild(label);
            });

        } catch (error) {
            listaDiv.innerHTML = `<p style="color: red;">Error al cargar: ${error.message}</p>`;
        }
    }

    /**
     * Listener para el envío del formulario de compartir.
     */
    document.getElementById('formCompartir').addEventListener('submit', async function(e) {
        e.preventDefault();
        const ruta = this.querySelector('#rutaCarpetaInput').value;
        const checkboxes = this.querySelectorAll('input[type="checkbox"]:checked');
        const usuariosSeleccionados = Array.from(checkboxes).map(cb => cb.value);

        try {
            const response = await fetch('gestor_compartir.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    accion: 'update_sharing',
                    ruta: ruta,
                    usuarios: usuariosSeleccionados
                })
            });

            const data = await response.json();
            
            if (data.ok) {
                alert('¡Configuración de carpeta compartida actualizada!');
                cerrarModal('modalCompartir');
                location.reload(); // Recargamos la página para ver el icono de cadena
            } else {
                throw new Error(data.mensaje);
            }

        } catch (error) {
            alert('Error al guardar los cambios: ' + error.message);
        }
    });


    // --- Lógica de actividad del usuario ---
    (function() {
        let ultimaActividad = Date.now();
        const enviarLatido = () => { fetch('latido.php', { method: 'POST' }).catch(console.error); ultimaActividad = Date.now(); };
        ['mousemove', 'click', 'keydown', 'scroll'].forEach(ev => window.addEventListener(ev, enviarLatido, { passive: true }));
        setInterval(() => { if (!document.hidden && (Date.now() - ultimaActividad < 180000)) enviarLatido(); }, 30000);

        const actualizarContador = () => {
            fetch('api_contador.php').then(r => r.ok ? r.text() : Promise.reject('Error')).then(c => {
                const b = document.getElementById('botonConectados');
                if (b) b.textContent = `🟢 Chat Conectados (${c})`;
            }).catch(console.error);
        };
        actualizarContador();
        setInterval(actualizarContador, 15000);
    })();

// --- Lógica del Poller ---
// 🔁 Poller único
(function notificarEventos() {
    const audioChat = document.getElementById('sonido-aviso-chat');
    const sonidoLlamada = document.getElementById('sonido-llamada');
    const popupChat = document.getElementById('popup-chat');
    const popupLlamada = document.getElementById('popup-llamada');
    const usuarioLlamandoEl = document.getElementById('llamada-de');
    let llamadaActiva = false;

    setInterval(() => {
        fetch('chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'mensajes_nuevos' })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.ok || !data.nuevos) return;

            const mensajes = data.nuevos;
            const llamada = mensajes.find(msg => msg.texto === 'videollamada');
            const textos = mensajes.filter(msg => msg.texto !== 'videollamada');

            // Mostrar popup de llamada si hay
            if (llamada && !llamadaActiva) {
                llamadaActiva = true;
                usuarioLlamandoEl.textContent = llamada.de;
                popupLlamada.style.display = 'block';
                if (sonidoLlamada) {
                    sonidoLlamada.loop = true;
                    sonidoLlamada.play().catch(() => {});
                }

                // Si no responde en 20 segundos = llamada perdida
                setTimeout(() => {
                    if (popupLlamada.style.display === 'block') {
                        popupLlamada.style.display = 'none';
                        sonidoLlamada.pause();
                        sonidoLlamada.currentTime = 0;
                        alert('📵 Llamada perdida de ' + llamada.de);
                        llamadaActiva = false;
                    }
                }, 20000);
            }

            // Mostrar mensajes de texto si hay
            if (textos.length > 0) {
                popupChat.style.display = 'block';
                document.title = `📨 (${textos.length}) Nuevo mensaje`;
                if (audioChat) audioChat.play().catch(() => {});

                textos.forEach(msg => {
                    const div = document.createElement('div');
                    div.className = 'popup-mensaje';
                    div.style.background = '#fff';
                    div.style.padding = '10px';
                    div.style.marginBottom = '5px';
                    div.style.borderRadius = '5px';
                    div.style.boxShadow = '0 0 10px rgba(0,0,0,0.1)';
                    div.innerHTML = `<strong>${msg.de}:</strong> ${msg.texto}`;
                    div.style.cursor = 'pointer';
                    div.onclick = () => window.open('chat.php?usuario=' + encodeURIComponent(msg.de), '_blank');
                    popupChat.appendChild(div);

                    setTimeout(() => {
                        div.remove();
                        if (popupChat.childElementCount === 0) {
                            popupChat.style.display = 'none';
                            document.title = "Explorador – Creawebes";
                        }
                    }, 7000);
                });
            }
        })
        .catch(console.warn);
    }, 5000);
})();

// 📲 Funciones para la llamada
function aceptarLlamada() {
    const usuario = document.getElementById('llamada-de').textContent;
    const usuarios = [miUsuarioDesdeIndex, usuario].sort();
    const nombreSala = `Creawebes_${usuarios[0]}_con_${usuarios[1]}`;
    window.open("https://meet.jit.si/" + nombreSala, "_blank");

    const sonido = document.getElementById('sonido-llamada');
    sonido.pause();
    sonido.currentTime = 0;
    document.getElementById('popup-llamada').style.display = 'none';
    llamadaActiva = false;
}

function rechazarLlamada() {
    const sonido = document.getElementById('sonido-llamada');
    sonido.pause();
    sonido.currentTime = 0;
    document.getElementById('popup-llamada').style.display = 'none';
    llamadaActiva = false;
}

// --- Llamada General ---
function abrirVideollamadaGeneral() {
  window.open("https://meet.jit.si/CreawebesSalaGeneral", "_blank");
}
