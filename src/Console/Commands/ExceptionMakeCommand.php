<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'module:make-exception')]
class ExceptionMakeCommand extends BaseGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-exception';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'module:make-exception';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new custom exception class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Exception';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        if ($this->option('render')) {
            return $this->option('report')
                ? $this->resolveStubPath('/stubs/exception-render-report.stub')
                : $this->resolveStubPath('/stubs/exception-render.stub');
        }

        return $this->option('report')
            ? $this->resolveStubPath('/stubs/exception-report.stub')
            : $this->resolveStubPath('/stubs/exception.stub');
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     */
    protected function alreadyExists($rawName): bool
    {
        return class_exists($this->rootNamespace().'Exceptions\\'.$rawName);
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Exceptions';
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the exception already exists'],
            ['render', null, InputOption::VALUE_NONE, 'Create the exception with an empty render method'],
            ['report', null, InputOption::VALUE_NONE, 'Create the exception with an empty report method'],
        ];
    }
}
