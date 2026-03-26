<?php
namespace Infrastructure\Service;

use PHPUnit\Framework\TestCase;

class PathSecurityServiceTest extends TestCase {
    private PathSecurityService $service;
    private string $rootDir;

    protected function setUp(): void {
        $this->rootDir = '/var/www/html/panel';
        $this->service = new PathSecurityService($this->rootDir);
    }

    public function testNormalizaRutaEliminaBarrasDuplicadasYBackslashes() {
        $rutaMala = '/var\\\\www//html\\/panel/';
        $this->assertEquals('/var/www/html/panel', $this->service->normalizaRuta($rutaMala));
    }

    public function testEstaDentroDePermiteArchivosEnElRoot() {
        $archivo = '/var/www/html/panel/archivo.txt';
        $this->assertTrue($this->service->estaDentroDe($archivo, $this->rootDir));
    }

    public function testEstaDentroDeBloqueaFugasDeDirectorio() {
        $this->assertFalse($this->service->estaDentroDe('/var/www/html/secret.txt', $this->rootDir));
    }

    public function testPuedeTocarRootIndexBloqueaAccesosNoAutorizados() {
        // Admin, override activo y vigente -> Permite
        $this->assertTrue($this->service->puedeTocarRootIndex(true, true, time() + 3600));
        
        // No admin -> Bloquea
        $this->assertFalse($this->service->puedeTocarRootIndex(false, true, time() + 3600));
        
        // Admin, pero sin override -> Bloquea
        $this->assertFalse($this->service->puedeTocarRootIndex(true, false, time() + 3600));
        
        // Admin, override activo pero caducado -> Bloquea
        $this->assertFalse($this->service->puedeTocarRootIndex(true, true, time() - 3600));
    }
}
