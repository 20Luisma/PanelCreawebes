<?php
require_once __DIR__ . '/verificar_sesion.php';
require_once __DIR__ . '/src/autoload.php';

use Presentation\Controller\BackupListController;

global $pathSecurityService;

$backupListController = new BackupListController($pathSecurityService, ROOT_DIR);
$backupListController->handleRequest($_GET);
