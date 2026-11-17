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
        $factory = $this->factoryClassBasename($name);

        $namespaceModel = $this->modelClass($name);

        $model = class_basename($namespaceModel);

        $replace = [
            '{{ factoryNamespace }}' => $this->factoryNamespace($namespaceModel),
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
        // Derive the file location from the namespace the class is written with, so the
        // file always matches PSR-4 ("Post" and "Post/PostFactory" both end up in
        // database/factories/Post/PostFactory.php when the model is Models\Post\Post).
        $subNamespace = Str::after(
            $this->factoryNamespace($this->modelClass($name)),
            $this->baseFactoryNamespace()
        );

        $directory = trim(str_replace('\\', DIRECTORY_SEPARATOR, $subNamespace), DIRECTORY_SEPARATOR);

        return $this->getModulePath(
            $this->getModuleInput(),
            'database'.DIRECTORY_SEPARATOR.'factories'.DIRECTORY_SEPARATOR
                .($directory !== '' ? $directory.DIRECTORY_SEPARATOR : '')
                .$this->factoryClassBasename($name).'Factory.php'
        );
    }

    /**
     * Get the factory class name without its "Factory" suffix (e.g. "Post").
     */
    protected function factoryClassBasename(string $name): string
    {
        return class_basename(Str::ucfirst(str_replace('Factory', '', $name)));
    }

    /**
     * Get the fully qualified model class the factory is for.
     */
    protected function modelClass(string $name): string
    {
        return $this->option('model')
            ? $this->qualifyModel($this->option('model'))
            : $this->qualifyModel($this->guessModelName($name));
    }

    /**
     * Get the module's base factory namespace (e.g. "Modules\Blog\Database\Factories").
     */
    protected function baseFactoryNamespace(): string
    {
        return $this->getDefaultNamespace(trim($this->rootNamespace(), '\\'));
    }

    /**
     * Get the factory namespace, mirroring the model's subdirectory.
     *
     * Laravel's HasFactory convention only looks under the application's Database\Factories,
     * so module models still point at their factory through a newFactory() override.
     *   Model:   Modules\Event\Models\Transaction\Transaction
     *   Factory: Modules\Event\Database\Factories\Transaction\TransactionFactory
     */
    protected function factoryNamespace(string $namespaceModel): string
    {
        $baseFactoryNamespace = $this->baseFactoryNamespace();
        $modelsKey = $this->rootNamespace().'Models\\';

        if (! Str::startsWith($namespaceModel, $modelsKey)) {
            return $baseFactoryNamespace;
        }

        $afterModels = Str::after($namespaceModel, $modelsKey); // e.g. "Transaction\Transaction"

        if (! Str::contains($afterModels, '\\')) {
            return $baseFactoryNamespace;
        }

        return $baseFactoryNamespace.'\\'.Str::beforeLast($afterModels, '\\'); // e.g. "...\Transaction"
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

        if (is_dir($this->getModulePath($this->getModuleInput(), 'src'.DIRECTORY_SEPARATOR.'Models'))) {
            return $this->qualifyModel($name);
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
     *
     * @return array<int, array<int, mixed>>
     */
    protected function getOptions(): array
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The name of the model'],
        ];
    }
}
