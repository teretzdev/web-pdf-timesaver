<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

final class Logger {
    private string $path;
    private int $maxBytes;
    private int $maxFiles;

    public function __construct(string $path = __DIR__ . '/../../logs/app.log', int $maxBytes = 1048576, int $maxFiles = 3) {
        $this->path = $path;
        $this->maxBytes = $maxBytes;
        $this->maxFiles = max(1, $maxFiles);
        $dir = dirname($this->path);
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
    }

    public function debug(string $msg, array $context = []): void { $this->write('DEBUG', $msg, $context); }
    public function info(string $msg, array $context = []): void { $this->write('INFO', $msg, $context); }
    public function error(string $msg, array $context = []): void { $this->write('ERROR', $msg, $context); }

    private function write(string $level, string $msg, array $context = []): void {
        $this->rotateIfNeeded();
        $contextStr = $this->formatContext($context);
        $line = sprintf("%s [%s] %s%s\n", date('c'), $level, $msg, $contextStr);
        @file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX);
    }

    private function formatContext(array $context): string {
        if (empty($context)) { return ''; }
        $parts = [];
        foreach ($context as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $parts[] = $k . '=' . (string)$v;
            } else {
                $parts[] = $k . '=' . json_encode($v);
            }
        }
        return ' ' . implode(' ', $parts);
    }

    private function rotateIfNeeded(): void {
        if (!file_exists($this->path)) { return; }
        $size = @filesize($this->path) ?: 0;
        if ($size < $this->maxBytes) { return; }
        // Rotate: app.log.(n) -> app.log.(n+1)
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $src = $this->path . '.' . $i;
            $dst = $this->path . '.' . ($i + 1);
            if (file_exists($src)) { @rename($src, $dst); }
        }
        @rename($this->path, $this->path . '.1');
    }
}
