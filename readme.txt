# 📁 Panel de Archivos Creawebes – README

Versión: 14 de junio de 2025  
Autor: Martín (con Alfred como copiloto de IA)

---

## 🔐 Acceso Seguro

El panel cuenta con un sistema de **login cifrado**:
- Usuario: `admin`
- Contraseña: cifrada con `password_hash` (la real es `creawebes2025`)
- Archivo responsable: `login.php`
- Control de sesión: si no estás logueado, `index.php` redirige automáticamente al `login.php`.
- El botón de cerrar sesión redirige a `logout.php`, el cual destruye la sesión.

---

## 📂 Explorador de Archivos (index.php)

Funciones disponibles desde la interfaz web:

### ✅ Acciones básicas:
- Crear carpetas
- Crear archivos `.txt`, `.html`, `.php`, etc.
- Editar archivos (solo si existe `editor.php`)
- Subir archivos desde el navegador
- Descargar archivos
- Descargar carpetas en `.zip` (si está activado en `download.php`)
- Duplicar archivos o carpetas

### 🧠 Acciones inteligentes:
- **Mover archivos o carpetas** a cualquier parte, incluyendo la raíz.
- Si el destino ya contiene un archivo/carpeta con el mismo nombre, **pregunta si deseas reemplazar**.
- No permite mover un archivo a su misma carpeta.

### 🧱 Seguridad:
- Toda navegación de carpetas está protegida con `realpath()` para evitar accesos fuera del directorio raíz.
- No se permiten rutas maliciosas (`../`) ni archivos ocultos del sistema.

---

## 💻 Archivos clave del sistema

- `index.php`: Explorador principal.
- `login.php`: Formulario de acceso seguro.
- `logout.php`: Cierre de sesión.
- `download.php`: Lógica para descargar archivos.
- `editor.php` (opcional): Para editar código en vivo.
- `panelcontrolcreawebes2025/`: Carpeta principal (opcional según tu estructura).

---

## 🎨 Estilo y experiencia visual

- Fuente moderna (Lato).
- Colores suaves azulados.
- Animación para el título `Creawebes` (efecto letra por letra).
- Modal profesional para mover, crear o confirmar acciones.

---

## 🚀 Cómo usar

1. Subí todos los archivos al servidor (por ejemplo: `contenido.creawebes.com`)
2. Asegurate de que `login.php`, `index.php` y `logout.php` estén en el mismo nivel.
3. Accedé desde el navegador a:  
   `https://contenido.creawebes.com/login.php`
4. Ingresá el usuario y contraseña para empezar.

---

## 📌 Recomendaciones

- Si se mueve una carpeta, el sistema recarga directamente la nueva ubicación.
- Si querés agregar funcionalidades nuevas (compresión zip, vista tipo tabla, multiusuario), el código está modularizado para escalar fácilmente.
- Ideal para uso personal o como panel de administración privado.

--

© 2025 – Creawebes
