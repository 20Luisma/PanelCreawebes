# 🖥️ Panel Creawebes

**Panel Creawebes** es un administrador de archivos web premium, seguro y de alto rendimiento, diseñado para gestionar entornos de hosting y aplicaciones web. Desarrollado originalmente como un panel de control monolítico, ha sido refactorizado por completo a **Clean Architecture**, convirtiéndose en una herramienta escalable, segura y mantenible.

---

## 🚀 Características Principales (El "Ferrari")

- **🔐 Seguridad Robusta:** 
  - Gestión centralizada de credenciales mediante `.env`.
  - Hashes robustos (`bcrypt`) para contraseñas de usuarios.
  - Hardening a nivel de servidor usando reglas `.htaccess` que bloquean acceso a carpetas del sistema.
  - Sistema de papelera protegida por contraseña para evitar borrados accidentales.
  
- **📁 Explorador de Archivos Avanzado:**
  - Navegación asíncrona rápida y limpia.
  - Subida (Drag & Drop), creación, renombrado, copia y movimiento seguro de archivos y carpetas.
  - Soporte completo para comprimir y descomprimir archivos (`.zip`).
  - Previsualización nativa de imágenes, vídeos, audios y documentos PDF directamente en el navegador sin descargas previas.

- **🧑‍💻 Editor de Código Integrado:**
  - Edición en vivo de cualquier archivo de texto (`.php`, `.js`, `.css`, etc.) utilizando **CodeMirror**.
  - Guardado asíncrono seguro y rápido con atajos de teclado y feedback visual.

- **👥 Gestión de Usuarios y Compartición:**
  - Creación dinámica de sub-usuarios.
  - Posibilidad de compartir carpetas específicas con usuarios seleccionados.
  - Vista en tiempo real de los **Usuarios Conectados** actualmente en la plataforma.

- **💬 Comunicación Integrada:**
  - Chat en vivo asíncrono incrustado en el propio panel para mantener comunicación entre los administradores y usuarios del servidor.

- **📐 Clean Architecture:**
  - Separación total entre **Presentation**, **Application**, **Domain** e **Infrastructure**.
  - Código desacoplado, fácil de testear, extender y mantener a lo largo del tiempo.

---

## 🛠️ Cómo Empezar

1. Clona el repositorio en la carpeta raíz del entorno de hosting:
   ```bash
   git clone https://github.com/20Luisma/PanelCreawebes.git .
   ```
2. Configura el entorno seguro copiando la plantilla:
   ```bash
   cp .env.example .env
   ```
3. Completa el archivo `.env` con las claves reales (Contraseña del sistema general, Papelera, Configuraciones de Mail).
4. Asegúrate de tener `PHP 7.4+` habilitado en el servidor, junto a las extensiones `zip`, `json` y `mbstring`.
5. ¡Listo! Todo acceso no autorizado será bloqueado por el nuevo `.htaccess`.

---

## 🧠 Flujo de Soporte IA (Nivel Dios)

Este proyecto está mantenido e integrado con comandos especiales de IA (Monster Project Manager):
- `/load_context`: Cargar la memoria e historia del proyecto en la IA.
- `/save_context`: Guardar el contexto actual de la conversación y generar backup automático.
- `/clean_context`: Compactar el registro histórico en `MEMORY.md`.
- `/analizar_escalabilidad`: Comando de auditoría que evalúa el estado del código base.

---

> Desarrollado con 🧉 pasión por el código limpio y la seguridad web.
