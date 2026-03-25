<?php
session_start();

if (isset($_SESSION['logueado']) && $_SESSION['logueado'] === true) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/src/autoload.php';

use Infrastructure\Persistence\FileUserRepository;
use Infrastructure\Persistence\FileActiveSessionRepository;
use Application\UseCase\LoginUseCase;
use Presentation\Controller\LoginController;

$usersFilePath = __DIR__ . '/usuarios.php';
$activityFilePath = __DIR__ . '/actividad_usuarios.json';
$activeSessionsFilePath = __DIR__ . '/usuarios_activos.json';

$userRepo = new FileUserRepository($usersFilePath, $activityFilePath);
$sessionRepo = new FileActiveSessionRepository($activeSessionsFilePath);
$loginUseCase = new LoginUseCase($userRepo, $sessionRepo);
$loginController = new LoginController($loginUseCase, $activityFilePath);

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = $loginController->handleRequest($_POST);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Acceso privado - Creawebes</title>
  <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700;900&display=swap" rel="stylesheet">
  <style>
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: 'Lato', sans-serif;
      background-color: #e3f2fd;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      min-height: 100vh;
      padding-top: 8vh;
    }
    
    /* --- INICIO ESTILOS PRELOADER AVANZADO --- */
    .preloader-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: #e3f2fd; /* Color de fondo base */
      z-index: 9999;
      display: none; /* Oculto por defecto, se activa con JS */
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: #3949ab;
    }
    .creawebes-logo {
        font-size: 4rem;
        font-weight: 900;
        margin-bottom: 1rem;
    }
    .counter {
        font-size: 2rem;
        font-weight: 700;
    }

    /* Animación del relámpago */
    @keyframes lightning-flash {
      0%, 100% {
        color: #3949ab;
        text-shadow: none;
      }
      50% {
        color: #ffffff;
        text-shadow: 0 0 10px #fff, 0 0 20px #fff, 0 0 30px #e3f2fd, 0 0 40px #3949ab;
      }
    }
    /* Clase que aplicaremos con JS para activar la animación */
    .flash-effect {
        animation: lightning-flash 0.6s ease-out;
    }
    /* --- FIN ESTILOS PRELOADER --- */

    form {
      background: white;
      padding: 3rem;
      border-radius: 14px;
      box-shadow: 0 0 40px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 450px;
      position: relative;
    }
    h2 {
      text-align: center;
      color: #3949ab;
      margin-bottom: 1.5rem;
    }
    input {
      width: 100%;
      margin-bottom: 1rem;
      padding: 0.75rem;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    button {
      background: #3949ab;
      color: white;
      padding: 0.6rem 1.2rem;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      display: block;
      margin: 1rem auto 0 auto;
      width: auto;
      min-width: 120px;
      transition: background-color 0.3s;
    }
    button:hover {
        background-color: #2c388a;
    }
    p {
      color: red;
      text-align: center;
      margin-bottom: 1rem;
    }
    .link-creawebes {
      text-align: center;
      margin-top: 1rem;
      font-size: 0.95rem;
    }
    .link-creawebes a {
      color: #3949ab;
      text-decoration: none;
      font-weight: bold;
    }
    .link-creawebes a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <!-- Contenedor del Preloader (Oculto por defecto) -->
  <div class="preloader-container" id="preloader">
    <div class="creawebes-logo" id="creawebes-logo">Creawebes</div>
    <div class="counter" id="counter">0%</div>
  </div>

  <!-- Etiqueta de audio para el sonido del relámpago -->
  <audio id="lightning-sound" src="relampago.mp3" preload="auto"></audio>

  <form method="POST" id="login-form">
    <h2>🔐 Acceso privado</h2>
    <?php if ($mensaje): ?><p><?= htmlspecialchars($mensaje) ?></p><?php endif; ?>
    <input type="text" name="usuario" placeholder="Usuario" required>
    <div style="position:relative; margin-bottom: 1rem;">
      <input type="password" name="clave" id="claveLogin" placeholder="Contraseña" required style="margin-bottom: 0;">
      <button type="button" onclick="const i=document.getElementById('claveLogin'); i.type=i.type==='password'?'text':'password'; this.textContent=i.type==='password'?'👁️':'🙈'" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);margin:0;background:none;border:none;cursor:pointer;font-size:1.2rem;padding:0;width:auto;min-width:auto;color:#888;">👁️</button>
    </div>
    <button type="submit">Entrar</button>
    <div class="link-creawebes">
      <a href="https://www.creawebes.com" target="_blank">🌐 Visitar creawebes.com</a>
    </div>
  </form>

  <!-- Script para la animación -->
  <script>
    const loginForm = document.getElementById('login-form');
    const preloader = document.getElementById('preloader');
    const creawebesLogo = document.getElementById('creawebes-logo');
    const counterSpan = document.getElementById('counter');
    const lightningSound = document.getElementById('lightning-sound');

    loginForm.addEventListener('submit', function(event) {
      // 1. Prevenimos que el formulario se envíe automáticamente
      event.preventDefault();
      
      // 2. Mostramos el contenedor de la animación
      preloader.style.display = 'flex';

      let count = 0;
      // 3. Creamos un intervalo que se ejecuta rápidamente para simular la carga
      const interval = setInterval(() => {
        count++;
        counterSpan.textContent = count + '%';

        // 4. A la mitad de la carga (50%), activamos el efecto
        if (count === 50) {
          creawebesLogo.classList.add('flash-effect'); // Añade la clase para la animación CSS
          lightningSound.play(); // Reproduce el sonido
        }

        // 5. Cuando el contador llega a 100
        if (count >= 100) {
          clearInterval(interval); // Detenemos el contador
          
          // Esperamos un instante para que el 100% sea visible antes de redirigir
          setTimeout(() => {
            loginForm.submit(); // Ahora sí, enviamos el formulario
          }, 300);
        }
      }, 25); // El número 25 (en ms) controla la velocidad de la carga. Más bajo = más rápido.
    });
    
    // Opcional: Limpiamos la clase de la animación cuando termina para que pueda repetirse
    creawebesLogo.addEventListener('animationend', () => {
        creawebesLogo.classList.remove('flash-effect');
    });
  </script>

</body>
</html>