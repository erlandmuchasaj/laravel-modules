<?php
declare(strict_types=1);

namespace ErlandMuchasaj\Modules\Support;

/**
 * Single place that resolves where modules live and which root namespace they use.
 *
 * Generators, runtime providers, commands and the Filament plugin all read these values
 * from here, so files are always generated where the application later looks for them.
 */
final class ModulePaths
{
    /**
     * The modules folder relative to the application root (default "modules").
     *
     * Supports the current `modules.folder` key and the legacy `modules.base` key.
     */
    public static function folder(): string
    {
        $folder = config('modules.folder') ?: config('modules.base', 'modules');

        return trim((string) $folder, '/\\') ?: 'modules';
    }

    /**
     * The root namespace shared by all modules (default "Modules").
     */
    public static function namespace(): string
    {
        return trim((string) config('modules.namespace', 'Modules'), '\\') ?: 'Modules';
    }
}
