<?php
declare(strict_types=1);

namespace ErlandMuchasaj\Modules\Traits;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Directory size helpers shared by the module:list and module:analyze commands.
 */
trait MeasuresDirectorySize
{
    /**
     * Get the total size in bytes of all files under the given directory (0 when it does not exist).
     */
    protected function getDirectorySize(string $path): int
    {
        if (! is_dir($path)) {
            return 0;
        }

        $size = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Format a byte count as a human-readable string (B, KB, MB, GB).
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = (int) floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
