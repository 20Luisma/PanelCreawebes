<?php
namespace Infrastructure\Service;

class FileEditorService {
    private string $root;
    private string $backupBase;

    public function __construct(string $rootPath) {
        $this->root = rtrim($rootPath, '/\\');
        $this->backupBase = $this->root . '/Historiales';
    }

    public function readFile(string $relativePath): string {
        $realPath = $this->getValidatedPath($relativePath);
        if (!$realPath || !is_file($realPath)) {
            throw new \Exception("File not found or invalid.");
        }
        return file_get_contents($realPath) ?: '';
    }

    public function getFileTime(string $relativePath): int {
        $realPath = $this->getValidatedPath($relativePath);
        return @filemtime($realPath) ?: 0;
    }

    public function saveFile(string $relativePath, string $contentBase64, int $clientMtime): bool {
        $realPath = $this->getValidatedPath($relativePath);
        if (!$realPath) {
            throw new \Exception("Invalid save path.");
        }

        $serverMtime = @filemtime($realPath);
        if ($serverMtime !== false && $serverMtime !== $clientMtime && $clientMtime !== 0) {
            throw new \Exception("Concurrency conflict", 409);
        }

        $content = base64_decode($contentBase64, true);
        if ($content === false) {
             throw new \Exception("Invalid Base64 content.");
        }

        $ok = @file_put_contents($realPath, $content, LOCK_EX);
        if ($ok !== false) {
            $this->createBackup($relativePath, $content);
            return true;
        }
        return false;
    }

    private function createBackup(string $relativePath, string $content): void {
        $rutaSub    = dirname($relativePath);
        $nombreBase = basename($relativePath);
        
        // Ensure path formatting is safe depending on OS (strip out leading ./ if any)
        if ($rutaSub === '.') $rutaSub = '';
        
        $dirBackup  = $this->backupBase . ($rutaSub ? '/' . $rutaSub : '') . '/' . $nombreBase;
        
        if (!is_dir($dirBackup)) {
            mkdir($dirBackup, 0775, true);
        }
        
        $stamp      = date('Ymd_His');
        $bakRel     = ($rutaSub ? "$rutaSub/" : '') . "$nombreBase/$stamp.txt";
        $bakAbs     = rtrim($this->backupBase, '/\\') . '/' . ltrim($bakRel, '/\\');
        
        @file_put_contents($bakAbs, $content, LOCK_EX);
    }

    private function getValidatedPath(string $relativePath): ?string {
        $absPath = $this->root . '/' . ltrim($relativePath, '/\\');
        $realPath = realpath($absPath);
        
        // Anti path-traversal check
        if ($realPath === false || strpos($realPath, $this->root) !== 0) {
            return null;
        }
        
        // Check allowed extensions (excluding fake folders tricks)
        $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        $permitidas = ['php','html','htm','js','css','json','xml','md','txt','java','py','ts'];
        if (!in_array($ext, $permitidas)) {
             return null;
        }

        return $realPath;
    }
}
