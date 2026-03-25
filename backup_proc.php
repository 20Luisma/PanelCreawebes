<?php
/**
 * backup_proc.php — Backend de backup (Clean Architecture)
 * Delega toda la lógica pesada al BackupService via BackupController.
 */
session_start();
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/src/autoload.php';

use Infrastructure\Service\BackupService;
use Presentation\Controller\BackupController;

$backupService = new BackupService(__DIR__);
$backupController = new BackupController($backupService);

$backupController->handleRequest($_POST);
