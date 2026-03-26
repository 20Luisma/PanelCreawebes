<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    die('⛔ Acceso solo para el administrador');
}

// --- LÓGICA PHP CON CLEAN ARCHITECTURE ---
require_once __DIR__ . '/src/autoload.php';

use Infrastructure\Persistence\FileUserRepository;
use Infrastructure\Service\PhpMailService;
use Application\UseCase\UserManagementUseCase;
use Presentation\Controller\UserController;

$usersFilePath = __DIR__ . '/usuarios.php';
$activityFilePath = __DIR__ . '/actividad_usuarios.json';
$usersBaseDir = __DIR__ . '/usuarios';

$repository = new FileUserRepository($usersFilePath, $activityFilePath);
$mailer = new PhpMailService();
$useCase = new UserManagementUseCase($repository, $mailer, $usersBaseDir);
$controller = new UserController($useCase, $repository);

$mensaje_get = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje_get = $controller->handleRequest($_POST);
} else {
    $mensaje_get = $_GET['mensaje'] ?? '';
}

// Preparar los arrays $usuarios y $actividad para la capa de Vista (retrocompatibilidad)
$usuarios = [];
$actividad = [];
$allUsers = $controller->getAllUsers();

foreach ($allUsers as $user) {
    $uName = $user->getUsername();
    $usuarios[$uName] = [
        'activo' => $user->isActive(),
        'admin'  => $user->isAdmin(),
        'clave'  => $user->getPasswordHash()
    ];
    
    $actividad[$uName] = [
        'nombre'   => $user->getFirstName(),
        'apellido' => $user->getLastName(),
        'email'    => $user->getEmail(),
        'ip'       => $user->getIp(),
        'accion'   => $user->getLastAction(),
        'fecha'    => $user->getLastActionDate()
    ];
}

// Asegurar que Luisma tenga sus datos
$actividad['Luisma'] = array_merge($actividad['Luisma'] ?? [], [
    'nombre' => 'Luis Martín',
    'apellido' => 'Pallante',
    'email' => 'martinpallante@gmail.com'
]);

?>
<!-- El resto del HTML de gestor_usuarios.php permanece igual -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Usuarios - Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --color-primario: #1e3a8a;
      --color-primario-hover: #1e40af;
      --color-bg-start: #f8fafc;
      --color-bg-end: #e2e8f0;
      --color-texto: #1e293b;
      --color-texto-ligero: #64748b;
      --color-blanco: #ffffff;
      --color-borde: #e2e8f0;
      --glass-bg: rgba(255, 255, 255, 0.7);
      --glass-border: 1px solid rgba(255, 255, 255, 0.8);
      --glass-shadow: 0 8px 32px rgba(30, 58, 138, 0.06);
      --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
      --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
      --shadow-lg: 0 12px 24px rgba(30, 58, 138, 0.12);
      --radio: 16px;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Outfit', sans-serif;
      background: linear-gradient(135deg, var(--color-bg-start) 0%, var(--color-bg-end) 100%);
      color: var(--color-texto);
      line-height: 1.6;
      padding: 2rem;
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
    }
    .container {
      max-width: 1400px;
      margin: 0 auto;
      display: grid;
      gap: 2rem;
    }
    header {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    h1 {
      font-size: 2.2rem;
      font-weight: 700;
      letter-spacing: -0.5px;
      background: linear-gradient(to right, var(--color-primario), #3b82f6);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    h2 {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--color-primario);
      margin-bottom: 1.5rem;
    }
    .card {
      background: var(--glass-bg);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: var(--glass-border);
      padding: 2rem;
      border-radius: var(--radio);
      box-shadow: var(--glass-shadow);
      animation: fadeIn 0.6s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(16px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .mensaje {
      padding: 1rem 1.2rem;
      border-radius: 10px;
      font-weight: 500;
      text-align: center;
      background: #eff6ff;
      border-left: 4px solid var(--color-primario);
      color: var(--color-primario);
      margin-bottom: 1.5rem;
    }
    .form-grid { display: grid; gap: 1rem; }
    input[type="text"], input[type="password"], input[type="email"] {
      width: 100%;
      padding: 0.9rem 1.1rem;
      font-size: 1rem;
      font-family: 'Outfit', sans-serif;
      border-radius: 10px;
      border: 1px solid var(--color-borde);
      background: rgba(255,255,255,0.9);
      transition: all 0.3s ease;
      outline: none;
    }
    input:focus {
      border-color: var(--color-primario);
      box-shadow: 0 0 0 4px rgba(30, 58, 138, 0.1);
      transform: translateY(-2px);
    }
    button[type="submit"] {
      width: 100%;
      padding: 1rem;
      font-size: 1.05rem;
      font-weight: 600;
      font-family: 'Outfit', sans-serif;
      background: linear-gradient(135deg, var(--color-primario) 0%, var(--color-primario-hover) 100%);
      color: var(--color-blanco);
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: var(--shadow-sm);
    }
    button[type="submit"]:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }
    /* Tabla */
    .table-wrapper { overflow-x: auto; border-radius: 12px; }
    .responsive-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.95rem;
    }
    .responsive-table th {
      background: linear-gradient(135deg, var(--color-primario), var(--color-primario-hover));
      color: white;
      padding: 1rem 1.2rem;
      text-align: left;
      font-weight: 600;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .responsive-table th:first-child { border-radius: 12px 0 0 0; }
    .responsive-table th:last-child { border-radius: 0 12px 0 0; }
    .responsive-table td {
      padding: 1rem 1.2rem;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
      background: white;
    }
    .responsive-table tr:last-child td { border-bottom: none; }
    .responsive-table tr:hover td { background: #eff6ff; }
    .estado-activo { color: #16a34a; font-weight: 600; }
    .estado-bloqueado { color: #dc2626; font-weight: 600; }
    .acciones { display: flex; gap: 0.5rem; align-items: center; }
    .acciones form { margin: 0; }
    .acciones button {
      width: auto;
      padding: 0.4rem 0.7rem;
      font-size: 1.1rem;
      line-height: 1;
      background: transparent;
      color: #6b7280;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      transition: all 0.2s;
      cursor: pointer;
    }
    .acciones button:hover {
      background: #eff6ff;
      border-color: var(--color-primario);
      transform: none;
      box-shadow: none;
    }
    .admin-toggle { transform: scale(1.4); cursor: pointer; accent-color: var(--color-primario); }
    @media (min-width: 768px) {
      .form-grid { grid-template-columns: repeat(2, 1fr); }
      .form-grid .full-width { grid-column: 1 / -1; }
    }
    @media screen and (max-width: 820px) {
      body { padding: 1rem; }
      .responsive-table thead { display: none; }
      .responsive-table, .responsive-table tbody, .responsive-table tr, .responsive-table td { display: block; width: 100%; }
      .responsive-table tr { margin-bottom: 1.5rem; border: 1px solid var(--color-borde); border-radius: var(--radio); box-shadow: var(--shadow-sm); overflow: hidden; }
      .responsive-table td { text-align: right; position: relative; padding-left: 50%; border-bottom: 1px solid #f1f5f9; }
      .responsive-table td:last-child { border-bottom: 0; }
      .responsive-table td::before { content: attr(data-label); position: absolute; left: 1rem; width: calc(50% - 2rem); padding-right: 10px; white-space: nowrap; text-align: left; font-weight: 600; color: var(--color-primario); }
      .acciones, .td-admin { justify-content: flex-end; }
    }
  </style>
</head>
<body>

<main class="container">
  
  <header>
    <h1>Gestión de Usuarios</h1>
    <?php if ($mensaje_get): ?>
      <div class="mensaje"><?= htmlspecialchars($mensaje_get) ?></div>
    <?php endif; ?>
  </header>

  <section class="card">
    <h2>👤 Crear Usuario Manual</h2>
    <form method="POST" autocomplete="off" class="form-grid">
      <input type="hidden" name="accion" value="crear_manual">
      <div><input type="text" name="nombre" placeholder="Nombre real" required></div>
      <div><input type="text" name="apellido" placeholder="Apellido" required></div>
      <div><input type="text" name="usuario" placeholder="Elija un nombre de usuario" required></div>
      <div><input type="email" name="email" placeholder="Correo electrónico" required></div>
      <div style="position:relative;"><input type="password" name="clave1" id="clave1" placeholder="Contraseña" required autocomplete="new-password"><button type="button" onclick="const i=document.getElementById('clave1'); i.type=i.type==='password'?'text':'password'; this.textContent=i.type==='password'?'👁️':'🙈'" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);margin:0;background:transparent;border:none;cursor:pointer;font-size:1.2rem;padding:0;width:auto;color:#888;">👁️</button></div>
      <div style="position:relative;"><input type="password" name="clave2" id="clave2" placeholder="Confirmar contraseña" required autocomplete="new-password"><button type="button" onclick="const i=document.getElementById('clave2'); i.type=i.type==='password'?'text':'password'; this.textContent=i.type==='password'?'👁️':'🙈'" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);margin:0;background:transparent;border:none;cursor:pointer;font-size:1.2rem;padding:0;width:auto;color:#888;">👁️</button></div>
      <div class="full-width"><button type="submit">➕ Crear Usuario</button></div>
    </form>
  </section>

  <section class="card">
    <h2>⚙️ Generar Usuario Automático</h2>
    <form method="POST" autocomplete="off" class="form-grid">
      <input type="hidden" name="accion" value="crear_auto">
      <div><input type="text" name="nombre_auto" placeholder="Nombre real" required></div>
      <div><input type="text" name="apellido_auto" placeholder="Apellido" required></div>
      <div class="full-width"><input type="email" name="email_auto" placeholder="Correo electrónico" required></div>
      <div class="full-width"><button type="submit">✨ Generar y Enviar</button></div>
    </form>
  </section>

  <section class="card">
    <h2>👥 Lista de Usuarios</h2>
    <div class="table-wrapper">
      <table class="responsive-table">
        <thead>
          <tr>
            <th>Usuario</th><th>Nombre</th><th>Apellido</th><th>Email</th><th>Estado</th><th>Admin</th><th>Actividad</th><th>IP</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($usuarios)): ?>
            <tr><td colspan="9" style="text-align: center; padding: 2rem;">No hay usuarios registrados.</td></tr>
          <?php else: ?>
            <?php
              $usuariosOrdenados = [];
              if (isset($usuarios['Luisma'])) {
                  $usuariosOrdenados['Luisma'] = $usuarios['Luisma'];
              }
              foreach (array_reverse($usuarios, true) as $user => $data) {
                  if ($user !== 'Luisma') {
                      $usuariosOrdenados[$user] = $data;
                  }
              }
            ?>
            <?php foreach ($usuariosOrdenados as $user => $data): ?>
              <tr>
                <td data-label="Usuario"><?= htmlspecialchars($user) ?></td>
                <td data-label="Nombre"><?= htmlspecialchars($actividad[$user]['nombre'] ?? '—') ?></td>
                <td data-label="Apellido"><?= htmlspecialchars($actividad[$user]['apellido'] ?? '—') ?></td>
                <td data-label="Email"><?= htmlspecialchars($actividad[$user]['email'] ?? '—') ?></td>
                <td data-label="Estado">
                  <span class="<?= $data['activo'] ? 'estado-activo' : 'estado-bloqueado' ?>">
                    <?= $data['activo'] ? '🟢 Activo' : '🔴 Bloqueado' ?>
                  </span>
                </td>
                <td data-label="Admin" class="td-admin">
                  <?php if ($user === 'Luisma'): ?>
                    <label style="display: flex; align-items: center; gap: 8px;">
                      <input type="checkbox" class="admin-toggle" checked disabled title="Superadministrador">
                      <span class="icono-corona" title="Administrador">👑</span>
                    </label>
                  <?php else: ?>
                    <form method="POST" style="display: inline;">
                      <input type="hidden" name="accion" value="toggle_admin">
                      <input type="hidden" name="usuario" value="<?= htmlspecialchars($user) ?>">
                      <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" class="admin-toggle" name="es_admin" onchange="this.form.submit()" title="Dar/quitar permisos de administrador" <?php echo ($data['admin'] ?? false) ? 'checked' : ''; ?>>
                        <?php if (!empty($data['admin'])): ?>
                          <span class="icono-corona" title="Administrador">👑</span>
                        <?php endif; ?>
                      </label>
                    </form>
                  <?php endif; ?>
                </td>
                <td data-label="Actividad">
  <button onclick="mostrarActividad('<?= htmlspecialchars($user, ENT_QUOTES) ?>')" title="Ver actividad de <?= htmlspecialchars($user) ?>" style="background: none; border: none; font-size: 18px; cursor: pointer; color: black;">📝</button>
</td>

                <td data-label="IP"><?= $actividad[$user]['ip'] ?? '—' ?></td>
                <td data-label="Acciones" class="acciones">
                  <?php if ($user !== 'Luisma'): ?>
                    <form method="POST"><input type="hidden" name="usuario" value="<?= htmlspecialchars($user) ?>"><input type="hidden" name="accion" value="<?= $data['activo'] ? 'bloquear' : 'activar' ?>"><button title="<?= $data['activo'] ? 'Bloquear usuario' : 'Activar usuario' ?>"><?= $data['activo'] ? '🚫' : '✅' ?></button></form>
                    <form method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar a este usuario de forma permanente? Esta acción no se puede deshacer.')"><input type="hidden" name="usuario" value="<?= htmlspecialchars($user) ?>"><input type="hidden" name="accion" value="eliminar"><button title="Eliminar usuario">🗑️</button></form>
                  <?php else: ?>
                    <span>—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main> <!-- Etiqueta </main> de cierre ÚNICA Y CORRECTA -->

<!-- MODAL de Actividad del Usuario (Versión Premium) -->
<div id="modalActividad" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalHeader" style="--modal-max-height: calc(100vh - 4rem);">
  <div class="modal-content">
    
    <h3 id="modalHeader" class="modal-header">📊 Actividad del Usuario</h3>
    
    <div id="contenidoActividad" class="modal-body">
      <!-- El contenido se generará aquí -->
    </div>

    <button id="cerrarModalBtn" class="modal-close-btn" aria-label="Cerrar ventana modal">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>
  </div>
</div>

<style>
  :root {
      --modal-bg: #ffffff;
      --modal-text: #333;
      --modal-header-color: #3949ab;
      --modal-border-radius: 12px;
      --modal-overlay-bg: rgba(0, 0, 0, 0.75);
      --modal-shadow: 0 10px 30px rgba(0,0,0,0.25);
  }

  .modal-overlay {
    display: none; 
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--modal-overlay-bg);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
  }

  .modal-overlay.visible {
    display: flex;
    opacity: 1;
  }

  .modal-content {
    background: var(--modal-bg);
    padding: 1.5rem 2rem 2rem 2rem;
    border-radius: var(--modal-border-radius);
    max-width: 500px;
    width: 90%;
    box-shadow: var(--modal-shadow);
    position: relative;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    transform: translateY(-20px) scale(0.95);
    transition: transform 0.3s ease-in-out;
    display: flex;
    flex-direction: column;
    max-height: var(--modal-max-height); /* Limita la altura máxima */
  }

  .modal-overlay.visible .modal-content {
    transform: translateY(0) scale(1);
  }
  
  .modal-header {
    margin: 0 0 1rem 0;
    color: var(--modal-header-color);
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
    text-align: center;
  }

  .modal-body {
    line-height: 1.6;
    color: var(--modal-text);
    overflow-y: auto; /* Habilita el scroll si el contenido es largo */
    padding-right: 1rem; /* Espacio para la barra de scroll */
    margin-right: -1rem;
  }
  
  .modal-body-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.95rem;
  }
  .modal-body-table td {
      padding: 0.5rem 0;
      border-bottom: 1px solid #f0f0f0;
  }
  .modal-body-table tr:last-child td {
      border-bottom: none;
  }
  .modal-body-table td:first-child {
      font-weight: 600;
      color: #555;
      width: 40%;
  }

  .modal-close-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: transparent;
    border: none;
    cursor: pointer;
    width: 36px;
    height: 36px;
    padding: 6px;
    border-radius: 50%;
    transition: background-color 0.2s, transform 0.2s;
  }
  .modal-close-btn svg {
    width: 100%;
    height: 100%;
    color: #999;
    transition: color 0.2s;
  }
  .modal-close-btn:hover {
    background-color: #f0f0f0;
    transform: scale(1.1);
  }
  .modal-close-btn:hover svg {
    color: #333;
  }
</style>

<script>
  const modal = document.getElementById('modalActividad');
  const contenidoActividad = document.getElementById('contenidoActividad');
  const cerrarModalBtn = document.getElementById('cerrarModalBtn');
  const actividad = <?= json_encode($actividad, JSON_UNESCAPED_UNICODE) ?>;
  
  let lastFocusedElement; // Para devolver el foco al cerrar

  // Define un orden y nombres amigables para los campos
  const ordenCampos = ['nombre', 'apellido', 'email', 'accion', 'fecha', 'ip'];
  const nombresCampos = {
    nombre: 'Nombre',
    apellido: 'Apellido',
    email: 'Email',
    accion: 'Última Acción',
    fecha: 'Fecha de Actividad',
    ip: 'Dirección IP'
  };

  function mostrarActividad(usuario) {
    lastFocusedElement = document.activeElement; // Guarda el elemento que abrió el modal
    const datos = actividad[usuario];

    if (!datos || Object.keys(datos).length === 0) {
      contenidoActividad.innerHTML = '<p style="text-align:center; color:#888;">No hay detalles de actividad disponibles.</p>';
    } else {
      let tableHtml = '<table class="modal-body-table">';
      // Itera en el orden definido
      ordenCampos.forEach(campo => {
        if (datos[campo]) {
          const nombreCampo = nombresCampos[campo] || campo.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
          tableHtml += `<tr><td>${nombreCampo}</td><td>${datos[campo]}</td></tr>`;
        }
      });
      // Añade campos no definidos en el orden al final
      for (const campo in datos) {
        if (!ordenCampos.includes(campo)) {
           const nombreCampo = nombresCampos[campo] || campo.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
           tableHtml += `<tr><td>${nombreCampo}</td><td>${datos[campo]}</td></tr>`;
        }
      }
      tableHtml += '</table>';
      contenidoActividad.innerHTML = tableHtml;
    }
    
    modal.classList.add('visible');
    // Mueve el foco al botón de cerrar al abrir el modal
    cerrarModalBtn.focus();
  }

  function cerrarModal() {
    modal.classList.remove('visible');
    // Devuelve el foco al elemento que abrió el modal
    if (lastFocusedElement) {
      lastFocusedElement.focus();
    }
  }
  
  // --- GESTIÓN DE EVENTOS Y ACCESIBILIDAD ---

  cerrarModalBtn.addEventListener('click', cerrarModal);

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      cerrarModal();
    }
  });

  // Listener para la tecla Escape y para atrapar el foco (Tab)
  document.addEventListener('keydown', (event) => {
    if (!modal.classList.contains('visible')) return;

    if (event.key === 'Escape') {
      cerrarModal();
    }
    
    if (event.key === 'Tab') {
      const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
      const firstElement = focusableElements[0];
      const lastElement = focusableElements[focusableElements.length - 1];

      if (event.shiftKey) { // Shift + Tab
        if (document.activeElement === firstElement) {
          lastElement.focus();
          event.preventDefault();
        }
      } else { // Tab
        if (document.activeElement === lastElement) {
          firstElement.focus();
          event.preventDefault();
        }
      }
    }
  });
</script>


</body>
</html>