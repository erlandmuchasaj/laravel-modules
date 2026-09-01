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

        $moduleSrcPath = base_path(
            'modules' . DIRECTORY_SEPARATOR . $this->getModuleInput() . DIRECTORY_SEPARATOR . 'src'
        );
        $name = Str::of($path)->after($moduleSrcPath)->beforeLast('.php')->append('Test')->replace('\\', '/');

        return $this->callSilent('module:make-test', [
            'module' => $this->getModuleInput(),
            'name' => $name,
            '--pest' => $this->option('pest'),
        ]) == 0;
    }

}
