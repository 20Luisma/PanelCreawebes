<?php
session_start();
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$root        = realpath(__DIR__);

/*  ⚠️  NUEVA CARPETA CON LOS ZIP  */
$backupDir   = $root . '/_backups/registros';   //  <-- aquí están los .zip
$zipName     = basename($_POST['zip'] ?? '');
$zipPath     = $backupDir . '/' . $zipName;

$tanda  = 40;                      // nº de ficheros por tanda
$accion = $_POST['accion'] ?? '';

if (!is_file($zipPath)) {
    echo json_encode(['error' => 'Backup no encontrado']);
    exit;
}

session_write_close();             // permite varias llamadas AJAX simultáneas

switch ($accion) {

    /* ---------- 1ª llamada: obtener total de archivos ---------- */
    case 'init':
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== TRUE) {
            echo json_encode(['error' => 'No se pudo abrir ZIP']); exit;
        }
        $total = $zip->numFiles;
        $zip->close();

        file_put_contents(sys_get_temp_dir().'/restore_state.json', json_encode([
            'zip'   => $zipPath,
            'total' => $total,
            'index' => 0
        ]));

        echo json_encode(['total' => $total, 'finalizado' => false]); exit;

    /* ---------- llamadas sucesivas: extraer “chunks” ---------- */
    case 'chunk':
        $stateFile = sys_get_temp_dir().'/restore_state.json';
        if (!is_file($stateFile)) {
            echo json_encode(['error' => 'Estado no encontrado']); exit;
        }
        $state = json_decode(file_get_contents($stateFile), true);

        $zip = new ZipArchive;
        if ($zip->open($state['zip']) !== TRUE) {
            echo json_encode(['error' => 'No se pudo abrir ZIP']); exit;
        }

        $inicio = $state['index'];
        $fin    = min($inicio + $tanda - 1, $state['total'] - 1);

        for ($i = $inicio; $i <= $fin; $i++) {
            $nombre = $zip->getNameIndex($i);
            if ($nombre) {
                $zip->extractTo($root, $nombre);   // sobrescribe
            }
        }
        $zip->close();

        $state['index'] = $fin + 1;
        file_put_contents($stateFile, json_encode($state));

        /* ---------- ¿terminamos? ---------- */
        if ($state['index'] >= $state['total']) {
            unlink($stateFile);                    // limpieza

            /* Carpeta de informes (la misma convención que backup) */
            $informesDir = $root . '/_informes_restore';
            
            if (!is_dir($informesDir)) {
                mkdir($informesDir, 0775, true);
                file_put_contents($informesDir . '/.htaccess', "Deny from all");
            }

            /* Informe individual y actualización del historial */
            $info  = "Restaurado desde : {$zipName}\n";
            $info .= "Fecha ZIP        : " . date('d-m-Y H:i', filemtime($zipPath)) . "\n";
            $info .= "Fecha restauración: " . date('d-m-Y H:i') . "\n";
            $info .= "Total archivos   : " . $state['total'] . "\n";
            $info .= str_repeat('-', 50) . "\n";

            file_put_contents(
                $informesDir . '/informe-restore-' . date('Ymd_His') . '.txt',
                $info
            );

            file_put_contents(
                $informesDir . '/historial_restore.txt',
                $info,
                FILE_APPEND
            );

            echo json_encode([
                'procesados' => $state['total'],
                'finalizado' => true
            ]);
            exit;
        }

        /*  Aún quedan archivos, devolvemos progreso  */
        echo json_encode([
            'procesados' => $state['index'],
            'finalizado' => false
        ]);
        exit;
}

echo json_encode(['error' => 'Acción inválida']);
