<?php
declare(strict_types=1);

namespace ErlandMuchasaj\Modules\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the module cache manager.
 *
 * @method static string modulesDirectory()
 * @method static array|null getModuleConfig(string $module)
 * @method static array getRegisteredModules()
 * @method static void cacheRoutesManifest(array $modules)
 * @method static array|null getRoutesManifest()
 * @method static array cacheMigrations(string $module)
 * @method static array cacheViews(string $module)
 * @method static void clearAll()
 * @method static void clearModule(string $module)
 * @method static void warmUp()
 * @method static array getStats()
 * @method static bool hasCached(string $module, string $type)
 *
 * @see \ErlandMuchasaj\Modules\Support\ModuleCacheManager
 */
class Module extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'modules';
    }
}
