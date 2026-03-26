<?php
// Incluimos verificar_sesion.php que ya inicia sesión y protege el endpoint
require_once __DIR__ . '/verificar_sesion.php';
session_write_close(); // Libera la sesión php

use Infrastructure\Service\ZipService;
use Application\UseCase\ZipUseCase;
use Presentation\Controller\ZipController;

// verificar_sesion.php define ROOT_DIR y prepara $pathSecurityService
global $pathSecurityService;

$zipService = new ZipService($pathSecurityService, ROOT_DIR);
$zipUseCase = new ZipUseCase($zipService, ROOT_DIR);
$zipController = new ZipController($zipUseCase);

$zipController->handleCompressRequest($_POST);
?>