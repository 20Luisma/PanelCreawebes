# Resumen del Proyecto
[Proyecto: Panel_Creawebes]

## Estado Actual
- Proyecto recién inicializado con Monster Project Manager.
- Tecnologías base: Vanilla HTML/CSS/JS.
- Arquitectura Base: Clean Architecture


## Decisiones Técnicas Core
1. Estructura y reglas inyectadas automáticamente.
2. **Contexto Migrado:** Este proyecto nace para refactorizar el panel monolítico de 28k líneas situado en `contenido.creawebes.com`.
3. **Objetivo:** Migrar código espagueti PHP a Clean Architecture purista sin romper el entorno de producción actual del servidor.

## Conocimiento del Dominio (Auditoría Inicial)
* **Estado Actual (Legacy):** El panel existente es un Mini SO Web. Maneja WebRTC (Videollamadas), Sistema de Chats en Vivo (sockets/polling), Backups Automáticos cronjob, Compresión ZIP y permisos de usuario. Todo está fuertemente acoplado en un formato procesal.
* **Archivos y DBs actuales:** Todo funciona leyendo archivos `.json` (ej: `compartidos.json`, `actividad_usuarios.json`) y usando `session_write_close()` agresivamente. 
* **Plan Quirúrgico a Futuro:**
  1. Descargar SOLO el panel (archivos PHP, JS, CSS) evitando descargas de carpetas clientes/usuarios pesadas en Hostinger.
  2. Aplicar Patrones de Repositorio para el manejo de JSON e inyectar *Controladores*.
  3. Desplegar mediante el "Monster PM" con despliegue diferencial y listas blancas súper estrictas para no borrar info de clientes.

## Historial de Sesiones
### 2026-03-23
- Creación de la estructura base usando Monster Project Manager.
- Transfusión exitosa de conocimiento y análisis arquitectónico desde la IA matriz hacia la memoria independiente de este proyecto.

### 2026-03-24
- **Ejecución del Plan Quirúrgico (Fase 1 completada):** Conexión exitosa por SSH a Hostinger.
- Extracción segura y de sólo lectura de los archivos core del panel (1.3 MB) hacia la carpeta local `/legaci`.
- Aislamiento exitoso: las carpetas pesadas de clientes (`usuarios` y `.papelera_creawebes`) fueron ignoradas en la descarga y recreadas vacías en local para evitar contaminación y ahorrar gigas. Producción sigue intacta.
- **Refactorización a Clean Architecture (Fase 2 completada):** Aislamiento de la lógica de negocio de `gestionar_usuarios.php` hacia Use Cases (`UserManagementUseCase`), Entidades puras y Repositorios JSON (`FileUserRepository`).
- Inserción de un Controlador (`UserController.php`) para conectar el nuevo back-end con las vistas legacy en HTML sin romper la UI.
- Escaneo de interfaz ocultando las tripas (carpetas nuevas de `src` y `vendor`) modificando el vector `$archivosSistema` de `index.php`.
- **Refactorización de Login (Fase 3 completada):** Limpieza absoluta procedural de `login.php`. Traslado de validación de contraseñas, seguridad activa y checking de concurrencia de 180s hacia `LoginUseCase`. Implementación de Repositorio propio (`FileActiveSessionRepository`) para el manejo aislado de `usuarios_activos.json`. Entrada perfecta validada.
- **Refactorización del Chat (Fase 4 completada):** `chat_api.php` pasó de 189 líneas de switch procedimental a 34 líneas con inyección de dependencias. Creación de `ChatUseCase` (5 métodos: enviar, historial, usuarios online, mensajes nuevos, videollamada), `FileChatRepository` para `mensajes.json`, `FileOnlineUsersRepository` que migra la función `obtenerUsuariosActivos()` de `funciones.php`. Probado en vivo con mensajes bidireccionales entre usuarios.
- **Refactorización de Backups (Fase 5 completada):** `backup_proc.php` pasó de 308→47 líneas y `restaurar_proc.php` de 249→72 líneas. Toda la lógica de ZipArchive, file-locking, manifiestos, rotación de 7 backups, protección CSRF, exclusiones de seguridad y chunking aislada en `BackupService.php` y `RestoreService.php` dentro de `src/Infrastructure/Service/`. Probado: backup al 100% y restaurar listando ZIPs correctamente.
- **Fix de Performance en Crear Archivo (Fase 6 completada):** `CrearNuevo Archivo.php` tenía un cuello de botella crítico: `obtenerCarpetas()` hacía un `scandir()` recursivo de 4 niveles de forma síncrona al cargar la página, bloqueando todo el render. Solución: creación de `api_carpetas.php` como endpoint AJAX liviano y refactorización del archivo para que el dropdown de carpetas se cargue en background vía `fetch()`. La página ahora renderiza instantáneamente. `api_carpetas.php` agregado a archivos de sistema ocultos en `index.php`.
- **Refactorización de Interfaz Editor y Chat (Fase 7 completada):** `editor.php` redujo su peso a la mitad aislando concurrencia (`mtime`), manejo de backups autoguardado, persistencia en Disco y token CSRF con `FileEditorService` y `FileEditorController`. `conectados.php` fue limpiado por completo de dependencias PHP e importaciones estáticas para transformarse en cliente puro consumiendo asincrónicamente `chat_api.php`. Archivos Legacy Restantes: 19.

### 2026-03-25
- **Refactorización de Seguridad y Sesiones (Fase 8 completada):** Separación completa de permisos de rutas (`PathSecurityService`) y validación por inactividad (`SessionVerificationUseCase`). El archivo `verificar_sesion.php` se redujo a un bootstrapper instanciable e inyector de dependencias. Archivos Legacy Restantes: 18.
- **Refactorización de Operaciones ZIP (Fase 9 completada):** Extracción de la lógica procedural de compresión y descompresión (archivos `comprimir.php` y `descomprimir.php`) hacia `ZipService`, `ZipUseCase` y `ZipController`. Se blindó a nivel de infraestructura el control de Zip-bombs (máx. 5GB o 50k archivos descomprimidos), y se cruzó lógicamente con `PathSecurityService` para evitar escapes de directorio y prevención nativa de ataques al `index.php`. Los archivos legacy ahora son solo inyectores. Archivos Legacy Restantes: 16.
- **Refactorización de Gestor de Compartidos (Fase 10 completada):** `gestor_compartir.php` migrado a Clean Architecture. Creación de `ShareRepositoryInterface` y `FileShareRepository` para lectura/escritura atómica de `compartidos.json`. Se implementó `ShareManagementUseCase` para centralizar permisos de dueños y admins, cruzando datos con `FileOnlineUsersRepository` (que se actualizó con un método nuevo para sacar el Nombre Real de la BD). Quedó sellado con `ShareController`. Archivos Legacy Restantes: 15.
- **Refactorización del Sistema de Descargas (Fase 11 completada):** `download.php` migrado a Clean Architecture. Se implementó `DownloadService` para encapsular la limpieza de buffers y transcodificación de streams binarios pesados (o carpetas completas en ZIP temporales con borrado automático). El `DownloadUseCase` se encarga de validar paths cruzados usando `PathSecurityService`, negando descaradamente "directory traversals" y descargas ilegales del `index.php`. El `DownloadController` despacha las brutas cabeceras restrictivas. Archivos Legacy Restantes: 14.
- **Refactorización del Sistema de Búsqueda (Fase 12 completada):** `buscar.php` migrado a Clean Architecture con `FileSearchService`, `SearchUseCase` y `SearchController`. Se reemplazó `scandir()` recursivo por iteradores nativos de PHP. Se agregó filtrado inteligente de archivos de sistema sincronizado con el toggle del admin (`sistemaVisible`): cuando el admin oculta archivos de sistema, la búsqueda también los filtra; cuando los desoculta, aparecen. Usuarios no-admin siempre ven resultados filtrados. Se corrigió bug del form que redirigía a JSON crudo al presionar Enter. Archivos Legacy Restantes: 13.
- **Refactorización del Sistema de Backups (Fase 13 completada):** `backup_proc.php`, `restaurar_proc.php` y `ver_backups.php` migrados a Controllers (`BackupController`, `RestoreController`, `BackupListController`). Los servicios ya existían; solo faltaba la capa de presentación. Se agregó validación de path en `BackupListController`. Archivos Legacy Restantes: 10.
- **Migración de Archivos Ligeros (Fase 14 completada):** `logout.php` → `LogoutUseCase` + `LogoutController` (3 ops atómicas de desconexión). `preview.php` → `PreviewController`. `api_carpetas.php` → `FolderTreeController` (con `PathSecurityService`). `api_contador.php` → `OnlineCountController` (absorbió lógica de `funciones.php`, que queda como código muerto). Archivos Legacy Restantes: 5.
- **Limpieza de UIs y Cron (Fase 15 completada):** `backup.php` ahora usa `verificar_sesion.php` + chequeo admin. `backup_cron.php` reescrito de 176 líneas duplicadas a 50 líneas usando `BackupService`. `funciones.php` ya no tiene importadores. Legacy pendientes: `index.php` (mega-archivo), `restaurar.php` (UI), `conectados.php` (UI).
- **Limpieza Final y Fix de Mover (Fase 16 completada):** Se corrigió el modal de Mover/Comprimir/Descomprimir para que respete el toggle de "Archivos de sistema", filtrando carpetas como `_backups`, `src`, `Historiales` del select de destino cuando el toggle está apagado. Se agregó parámetro `&sistema=1` a los 4 fetch del frontend y filtrado por `$archivosSistema` en el endpoint `?listar=1` del backend. QA pasada: login, logout, buscar, mover, backup, chat — todo funcional.
- **Megarefactor de index.php (Fase 17 completada):** Se desfragmentó exitosamente el archivo raíz (de 1600 líneas a menos de la mitad). El CSS se externalizó a `assets/panel.css`, el JS a `assets/panel.js`, y los modales HTML a `partials/modales.php`. La inyección de variables se resolvió limpiamente con `<script type="application/json">` (`panel-config`). Se resolvió una falsa alarma (falso positivo del subagente) en el testing de modales de Mover y Comprimir. ¡Con esto se logra la migración total a Clean Architecture de los 21 endpoints del proyecto original! 🚀
- **Implementación de `.env` y Reestructuración de Carpetas (Fase 18 completada):**
  - Se creó el archivo `.env` con las credenciales extraídas del código: `SISTEMA_PASSWORD`, `MAIL_FROM`, `PANEL_URL`.
  - Se implementó un parser liviano de `.env` en `verificar_sesion.php` que carga las variables en `$_ENV` automáticamente en cada request.
  - Se actualizó `panel.js` para leer la contraseña del sistema desde `panelConfig.sistemaPassword` (inyectada desde `$_ENV`).
  - Se actualizó `PhpMailService.php` para usar `$_ENV['MAIL_FROM']` y `$_ENV['PANEL_URL']`.
  - **Mudanza `legaci/` → raíz:** Todo el contenido de la carpeta `legaci/` fue movido a la raíz del proyecto con `rsync --remove-source-files`. La carpeta `legaci/` fue eliminada. Las URLs de los endpoints no cambiaron ni 1mm (compatibilidad total con apps externas y datos de Hostinger).
  - **Limpieza visual del panel:** Se agregaron 80+ archivos al array `$archivosSistema` en `index.php` (incluyendo `.env`, `.gitignore`, `.vscode`, `.agents`, `MEMORY.md`, `assets/`, `partials/`, `test/`, docs, backups dinámicos con `glob()`). El panel ahora muestra solo contenido de usuario.
  - Se movieron archivos de test sueltos a la carpeta `test/`.
  - Se creó `.htaccess` de producción que bloquea acceso HTTP a `src/`, `.env`, `partials/`, `.agents/`, `.vscode/` y archivos `.md`.
  - Se actualizó `.gitignore` para proteger datos de usuario, backups, JSONs y archivos de test.
  - Se creó `.env.example` como plantilla segura sin credenciales reales.
  - **Mejoras UX/UI:** Se agregaron botones de "ver contraseña" (👁️/🙈) en todos los inputs de contraseña del panel (`login.php`, `gestionar_usuarios.php`, `modales.php` de sistema), asegurando alineación perfecta en CSS usando un wrapper relativo y sobrescribiendo márgenes de botón globales.
- **Estado actual:** Proyecto 100% migrado a Clean Architecture. Estructura limpia en la raíz. Credenciales en `.env`. Todas las interfaces pulidas y alineadas. Listo para deploy en `contenido.creawebes.com` (URLs relativas aseguran compatibilidad automática).
