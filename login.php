<?php
session_start();
if (isset($_SESSION['logueado']) && $_SESSION['logueado'] === true) {
    header('Location: index.php');
    exit;
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioCorrecto = 'Luisma';
    $hashGuardado = '$2y$10$1StFYhQsnW4uzoWXUImi9OpMCpJRtIxDw9Z03dYKJvDhfVoaHBGie'; // 20Septiembre1981

    $usuario = $_POST['usuario'] ?? '';
    $clave   = $_POST['clave'] ?? '';

    if ($usuario === $usuarioCorrecto && password_verify($clave, $hashGuardado)) {
        $_SESSION['logueado'] = true;
        header('Location: index.php');
        exit;
    } else {
        $mensaje = '❌ Usuario o clave incorrecta';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Acceso privado - Creawebes</title>
  <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
  <style>
  html, body {
  height: 100%;
  margin: 0;
  padding: 0;
  overflow: hidden; /* ✅ Esto evita el scroll */
}
body {
  font-family: 'Lato', sans-serif;
  background-color: #e3f2fd;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;     /* ✅ Alínea arriba */
  min-height: 100vh;
  margin: 0;
  padding-top: 8vh;                /* ✅ Margen superior visual */
}


form {
  background: white;
  padding: 3rem 3rem;          /* ✅ agrandamos el contorno */
  border-radius: 14px;
  box-shadow: 0 0 40px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 450px;            /* ✅ un poco más ancho */
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
  margin: 1rem auto 0 auto;  /* ✅ lo centra horizontalmente */
  width: auto;               /* ✅ más pequeño */
  min-width: 120px;          /* ✅ evita que quede muy chico */
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
  <form method="POST">
    <h2>🔐 Acceso privado</h2>
    <?php if ($mensaje): ?><p><?= $mensaje ?></p><?php endif; ?>
    <input type="text" name="usuario" placeholder="Usuario" required>
    <input type="password" name="clave" placeholder="Contraseña" required>
    <button type="submit">Entrar</button>

    <div class="link-creawebes">
      <a href="https://www.creawebes.com" target="_blank">🌐 Visitar creawebes.com</a>
    </div>
  </form>
</body>
</html>
