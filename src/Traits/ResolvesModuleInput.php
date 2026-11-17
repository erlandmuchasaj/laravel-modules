<?php
declare(strict_types=1);

namespace ErlandMuchasaj\Modules\Traits;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Reads and validates the "module" argument of a console command.
 */
trait ResolvesModuleInput
{
    /**
     * Get the StudlyCase module name from the input.
     *
     * @throws InvalidArgumentException when the name contains anything but letters, numbers and underscores
     */
    protected function getModuleInput(): string
    {
        $name = Str::of((string) $this->argument('module'))->trim()->studly()->toString();

        // Reject separators and dot segments so the name can never escape the modules folder
        // or be interpreted by a shell when it is forwarded to composer.
        if (! preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new InvalidArgumentException('Module name may only contain letters, numbers and underscores.');
        }

        return $name;
    }
}
