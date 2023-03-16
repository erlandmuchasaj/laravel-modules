<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'module:make-listener')]
class ListenerMakeCommand extends BaseGeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-listener';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'module:make-listener';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new event listener class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Listener';

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     *
     * @throws FileNotFoundException
     */
    protected function buildClass($name): string
    {
        $event = $this->option('event');

        if (! Str::startsWith($event, [
            $this->rootNamespace(),
            'Illuminate',
            '\\',
        ])) {
            $event = $this->rootNamespace().'Events\\'.str_replace('/', '\\', $event);
        }

        $stub = str_replace(
            ['DummyEvent', '{{ event }}'], class_basename($event), parent::buildClass($name)
        );

        return str_replace(
            ['DummyFullEvent', '{{ eventNamespace }}'], trim($event, '\\'), $stub
        );
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        if ($this->option('queued')) {
            return $this->option('event')
                ? $this->resolveStubPath('/stubs/listener-queued.stub')
                : $this->resolveStubPath('/stubs/listener-queued-duck.stub');
        }

        return $this->option('event')
            ? $this->resolveStubPath('/stubs/listener.stub')
            : $this->resolveStubPath('/stubs/listener-duck.stub');
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     */
    protected function alreadyExists($rawName): bool
    {
        return class_exists($this->rootNamespace().'Listeners\\'.$rawName);
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Listeners';
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function getOptions(): array
    {
        return [
            ['event', 'e', InputOption::VALUE_OPTIONAL, 'The event class being listened for'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the listener already exists'],
            ['queued', null, InputOption::VALUE_NONE, 'Indicates the event listener should be queued'],
        ];
    }
}
