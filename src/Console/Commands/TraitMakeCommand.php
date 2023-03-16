<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'module:make-trait')]
class TraitMakeCommand extends BaseGeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make-trait {module} {name} {--model=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make trait';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Trait';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/trait.stub');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        if ($model = $this->option('model')) {
            return $rootNamespace.'\\Models\\'.$model;
        } else {
            return $rootNamespace.'\\Traits';
        }
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function getOptions(): array
    {
        return [
            ['model', null, InputOption::VALUE_OPTIONAL, 'If trait should be generated inside model'],
        ];
    }
}
