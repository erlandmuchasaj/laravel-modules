<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Illuminate\Support\Str;

trait CreatesMatchingTest
{
    use \Illuminate\Console\Concerns\CreatesMatchingTest {
        handleTestCreation as baseHandleTestCreation;
    }

    /**
     * Create the matching test case if requested.
     */
    protected function handleTestCreation($path): bool
    {
        if (! $this->option('test') && ! $this->option('pest')) {
            return false;
        }

        return $this->callSilent('module:make-test', [
            'module' => $this->getModuleInput(),
            'name' => Str::of($path)->after($this->laravel['path'])->beforeLast('.php')->append('Test')->replace('\\', '/'),
            '--pest' => $this->option('pest'),
        ]) == 0;
    }

}
