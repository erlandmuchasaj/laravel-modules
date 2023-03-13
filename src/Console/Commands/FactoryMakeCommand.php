<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'module:make-factory')]
class FactoryMakeCommand extends BaseGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-factory';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'module:make-factory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model factory';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Factory';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/factory.stub');
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     *
     * @throws FileNotFoundException
     */
    protected function buildClass($name): string
    {
        $factory = class_basename(Str::ucfirst(str_replace('Factory', '', $name)));

        $namespaceModel = $this->option('model')
                        ? $this->qualifyModel($this->option('model'))
                        : $this->qualifyModel($this->guessModelName($name));

        $model = class_basename($namespaceModel);

        if (Str::startsWith($namespaceModel, $this->rootNamespace().'Models')) {
            $namespace = Str::beforeLast($this->getDefaultNamespace(trim($this->rootNamespace(), '\\')).'\\'.Str::after($namespaceModel, $this->rootNamespace().'Models\\'), '\\');
        } else {
            $namespace = $this->getDefaultNamespace(trim($this->rootNamespace(), '\\'));
        }

        $replace = [
            '{{ factoryNamespace }}' => $namespace,
            'NamespacedDummyModel' => $namespaceModel,
            '{{ namespacedModel }}' => $namespaceModel,
            '{{namespacedModel}}' => $namespaceModel,
            'DummyModel' => $model,
            '{{DummyModel}}' => $model,
            '{{ DummyModel }}' => $model,
            '{{ model }}' => $model,
            '{{model}}' => $model,
            '{{ factory }}' => $factory,
            '{{factory}}' => $factory,
        ];

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     */
    protected function getPath($name): string
    {
        $name = (string) Str::of($name)->replaceFirst($this->rootNamespace(), '')->finish('Factory');

        $path = 'modules'.DIRECTORY_SEPARATOR.$this->getModuleInput().DIRECTORY_SEPARATOR.'database'
            .DIRECTORY_SEPARATOR.'factories'.DIRECTORY_SEPARATOR.str_replace('\\', '/', $name).'.php';

        return base_path($path);
    }

    /**
     * Guess the model name from the Factory name or return a default model name.
     */
    protected function guessModelName(string $name): string
    {
        if (Str::endsWith($name, 'Factory')) {
            $name = substr($name, 0, -7);
        }

        $modelName = $this->qualifyModel(Str::after($name, $this->rootNamespace()));

        if (class_exists($modelName)) {
            return $modelName;
        }

        $modelPath = base_path("modules/{$this->getModuleInput()}/src/Models/");
        if (is_dir($modelPath)) {
            return $this->rootNamespace().'Models\Model';
        }

        return $this->rootNamespace().'Model';
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string  $name
     */
    protected function qualifyClass($name): string
    {
        return $name;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Database\\Factories';
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The name of the model'],
        ];
    }
}
