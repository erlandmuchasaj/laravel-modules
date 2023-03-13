<?php

namespace ErlandMuchasaj\Modules\Facades;

use Illuminate\Support\Facades\Facade;

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
