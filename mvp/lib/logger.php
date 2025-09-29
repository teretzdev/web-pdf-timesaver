<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

final class Logger {
    private string $path;

    public function __construct(string $path = __DIR__ . '/../../logs/app.log') {
        $this->path = $path;
        $dir = dirname($this->path);
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    }

    public function info(string $msg): void { $this->write('INFO', $msg); }
    public function error(string $msg): void { $this->write('ERROR', $msg); }

    private function write(string $level, string $msg): void {
        $line = sprintf("%s [%s] %s\n", date('c'), $level, $msg);
        @file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX);
    }
}
