    <div id="modalMover" class="modal">
        <div class="modal-content">
            <h2>📂 Mover elemento(s)</h2>
            <form method="POST" id="formMover">
                <input type="hidden" name="accion" value="" id="accionMover">
                <input type="hidden" name="archivo" value="" id="moverArchivo">
                <input type="hidden" name="archivos_json" id="moverArchivosJson">
                <label>Destino:</label>
                <select name="destino" id="selectDestino" style="width:100%;margin:1rem 0; padding: 0.5rem;"></select>
                <button class="btn-top" type="submit">✅ Mover</button>
                <button class="btn-top" type="button" onclick="document.getElementById('modalMover').style.display='none'">❌ Cancelar</button>
            </form>
        </div>
    </div>
    <?php if ($error === 'conflicto' && isset($_GET['archivo'], $_GET['destino'])): ?>
    <div class="modal" style="display:flex;">
        <div class="modal-content">
            <h2>⚠️ El elemento ya existe</h2>
            <p><strong><?= htmlspecialchars(basename($_GET['archivo'])) ?></strong> ya existe en <strong><?= $_GET['destino'] !== '' ? htmlspecialchars($_GET['destino']) : 'Inicio' ?></strong>.</p>
            <p>¿Deseas reemplazarlo?</p>
            <form method="POST" style="text-align:center;">
                <input type="hidden" name="accion"  value="mover">
                <input type="hidden" name="archivo" value="<?= htmlspecialchars($_GET['archivo']) ?>">
                <input type="hidden" name="destino" value="<?= htmlspecialchars($_GET['destino']) ?>">
                <input type="hidden" name="forzar"  value="1">
                <button class="btn-top" type="submit">✅ Sí, reemplazar</button>
                <a class="btn-top" href="index.php?carpeta=<?= urlencode($carpetaRelativa) ?>">❌ Cancelar</a>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($error === 'conflicto_multiple' && isset($_GET['archivos'], $_GET['destino'])): ?>
        <div class="modal" style="display:flex;">
            <div class="modal-content">
                <h2>⚠️ Algunos elementos ya existen</h2>
                <p>Uno o más elementos ya existen en la carpeta <strong><?= htmlspecialchars($_GET['destino']) ?: 'Inicio' ?></strong>.</p>
                <ul style="text-align:left;margin:1rem auto 1rem auto;max-height:180px;overflow:auto;padding:0 1rem;"><?php $lista = json_decode($_GET['archivos'] ?? '[]', true); foreach ($lista as $nombre) { echo '<li>📄 ' . htmlspecialchars(basename($nombre)) . '</li>'; } ?></ul>
                <p style="margin-top:1rem;">¿Deseas reemplazarlos?</p>
                <form method="POST" action="index.php" style="text-align:center;">
                    <input type="hidden" name="accion" value="mover_multiple">
                    <input type="hidden" name="destino" value="<?= htmlspecialchars($_GET['destino']) ?>">
                    <input type="hidden" name="forzar" value="1">
                    <input type="hidden" name="archivos_json" value='<?= htmlspecialchars($_GET['archivos']) ?>'>
                    <button type="submit" class="btn-top">✅ Sí, reemplazar</button>
                    <a class="btn-top" href="index.php?carpeta=<?= urlencode($carpetaRelativa) ?>">❌ Cancelar</a>
                </form>
            </div>
        </div>
    <?php endif; ?>
    <div id="modalComprimir" class="modal"><div class="modal-content"><h2>📦 Comprimir archivo(s) seleccionado(s)</h2><form id="formComprimir" method="POST" action="comprimir.php"><label>Nombre del ZIP:</label><br><input type="text" name="nombre_zip" value="backup_<?= date('Y-m-d') ?>.zip" required style="width:100%; padding:8px; margin-bottom:10px;"><br><br><label>Destino:</label><br><select name="destino" style="width:100%; padding:8px; margin-bottom:10px;"></select><br><br><input type="hidden" name="archivos_json"><button type="submit" class="btn-top">✅ Comprimir</button><button type="button" class="btn-top" onclick="cerrarModal('modalComprimir')">❌ Cancelar</button></form></div></div>
    <div id="modalDescomprimir" class="modal"><div class="modal-content"><h2>📂 Descomprimir archivo ZIP</h2><form id="formDescomprimir" method="POST" action="descomprimir.php"><label>Destino:</label><br><select name="destino" style="width:100%; padding:8px; margin-bottom:10px;"></select><br><br><input type="hidden" name="archivo"> <button type="submit" class="btn-top">✅ Descomprimir</button><button type="button" class="btn-top" onclick="cerrarModal('modalDescomprimir')">❌ Cancelar</button></form></div></div>
    <div id="modalSistema" class="modal" style="display:none;"><div class="modal-content"><h2>🔐 Acceso a archivos del hệstema</h2><p>Introduce la contraseña para continuar:</p><div style="position:relative; margin:10px 0;"><input type="password" id="claveSistema" placeholder="Contraseña" style="width:100%; padding:10px; margin:0;"><button type="button" onclick="const i=document.getElementById('claveSistema'); i.type=i.type==='password'?'text':'password'; this.textContent=i.type==='password'?'👁️':'🙈'" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:transparent;border:none;cursor:pointer;font-size:1.2rem;padding:0;width:auto;color:#888;">👁️</button></div><div style="text-align:right;"><button class="btn-top" onclick="verSistema()">✅ Ver</button><button class="btn-top" onclick="cerrarModal('modalSistema')">❌ Cancelar</button></div></div></div>

    <!-- ### NUEVO ### Modal para Compartir Carpetas -->
    <div id="modalCompartir" class="modal">
        <div class="modal-content">
            <h2 style="display: flex; align-items: center; gap: 10px;">
               <span style="font-size: 1.5rem;">🔗</span> Compartir Carpeta
            </h2>
            <p>Selecciona los usuarios con los que quieres compartir: <strong id="nombreCarpetaCompartir"></strong></p>
            <form id="formCompartir">
                <input type="hidden" name="ruta_carpeta" id="rutaCarpetaInput">
                <div id="listaUsuariosCompartir" style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 1rem; border-radius: 5px; margin: 1rem 0;">
                    <!-- La lista de usuarios se generará aquí con JS -->
                </div>
                <button class="btn-top" type="submit">✅ Guardar cambios</button>
                <button class="btn-top" type="button" onclick="cerrarModal('modalCompartir')">❌ Cancelar</button>
            </form>
        </div>
    </div>
