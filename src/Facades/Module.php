<?php
declare(strict_types=1);

namespace ErlandMuchasaj\Modules\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed someModuleMethod(...$args) Example method annotation
 * Facade for the module service.
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
