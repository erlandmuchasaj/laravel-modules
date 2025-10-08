<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UnhandledMatchError;

#[AsCommand(name: 'module:make-test')]
class TestMakeCommand extends BaseGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-test';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'module:make-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new test class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Test';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        $suffix = $this->option('unit') ? '.unit.stub' : '.stub';

        return $this->option('pest')
            ? $this->resolveStubPath('/stubs/pest'.$suffix)
            : $this->resolveStubPath('/stubs/test'.$suffix);
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     */
    protected function getPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        $path = 'modules'.DIRECTORY_SEPARATOR.$this->getModuleInput().DIRECTORY_SEPARATOR.'tests'
            .DIRECTORY_SEPARATOR.str_replace('\\', '/', $name).'.php';

        return base_path($path);
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        if ($this->option('unit')) {
            return $rootNamespace.'\\Unit';
        } else {
            return $rootNamespace.'\\Feature';
        }
    }

    /**
     * Get the root namespace for the class.
     */
    protected function rootNamespace(): string
    {
        $moduleName = $this->getModuleInput();

        return "Modules\\$moduleName\\Tests";
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the test already exists'],
            ['unit', 'u', InputOption::VALUE_NONE, 'Create a unit test.'],
            ['pest', 'p', InputOption::VALUE_NONE, 'Create a Pest test.'],
        ];
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        $type = $this->components->choice('Which type of test would you like', [
            'feature' => 'Feature (PHPUnit)',
            'unit' => 'Unit (PHPUnit)',
            'pest-feature' => 'Feature (Pest)',
            'pest-unit' => 'Unit (Pest)',
        ], default: 'feature');

        match ($type) {
            'feature' => null,
            'unit' => $input->setOption('unit', true),
            'pest feature' => $input->setOption('pest', true),
            'pest unit' => tap($input)->setOption('pest', true)->setOption('unit', true),
            default => throw new UnhandledMatchError($type),
        };
    }
}
