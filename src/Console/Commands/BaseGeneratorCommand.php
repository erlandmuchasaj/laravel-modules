<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use ErlandMuchasaj\Modules\Support\ModulePaths;
use ErlandMuchasaj\Modules\Traits\ResolvesModuleInput;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;

abstract class BaseGeneratorCommand extends GeneratorCommand
{
    use ResolvesModuleInput;

    /**
     * Execute the console command.
     *
     *
     * @throws FileNotFoundException
     */
    public function handle(): int|bool|null
    {
        // check if a module is already created and a file exists
        if (! $this->moduleAlreadyExists()) {
            $this->components->error(
                sprintf('Module [%s] does not exists, Please create a module first.', $this->getModuleInput())
            );

            return false;
        }

        return parent::handle();
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     */
    protected function getPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->getModulePath(
            $this->getModuleInput(),
            'src'.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $name).'.php'
        );
    }

    /**
     * Get the first view directory path from the application configuration.
     *
     * @param  string  $path
     */
    protected function viewPath($path = ''): string
    {
        $views = $this->getModulePath($this->getModuleInput(), 'resources'.DIRECTORY_SEPARATOR.'views');

        return $views.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Resolve the fully qualified path to the stub.
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Replace the namespace for the given stub.
     *
     * We overwrite this command to put laravel App root name dynamically.
     *
     * @example $this->rootNamespace() => $this->laravel->getNamespace()
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name): static
    {
        $searches = [
            ['DummyNamespace', 'DummyRootNamespace', 'NamespacedDummyUserModel'],
            ['{{ namespace }}', '{{ rootNamespace }}', '{{ namespacedUserModel }}'],
            ['{{namespace}}', '{{rootNamespace}}', '{{namespacedUserModel}}'],
        ];

        foreach ($searches as $search) {
            $stub = str_replace(
                $search,
                [$this->getNamespace($name), $this->laravel->getNamespace(), $this->userProviderModel()],
                $stub
            );
        }

        return $this;
    }

    /**
     * Get a list of possible model names.
     * Based on Module generated
     *
     * @return array<int, string>
     */
    protected function possibleModels(): array
    {
        $modelPath = $this->getModulePath($this->getModuleInput(), 'src'.DIRECTORY_SEPARATOR.'Models');

        if (! is_dir($modelPath)) {
            $modelPath = is_dir(app_path('Models')) ? app_path('Models') : app_path();
        }

        return collect(Finder::create()->files()->depth(['>= 0', '< 2'])->in($modelPath))
            ->map(fn ($file) => $file->getBasename('.php'))
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Get a list of possible event names.
     * Based on Generated Module
     *
     * @return array<int, string>
     */
    protected function possibleEvents(): array
    {
        $eventPath = $this->getModulePath($this->getModuleInput(), 'src'.DIRECTORY_SEPARATOR.'Events');

        if (! is_dir($eventPath)) {
            return [];
        }

        return collect((new Finder)->files()->depth(0)->in($eventPath))
            ->map(fn ($file) => $file->getBasename('.php'))
            ->values()
            ->all();
    }

    /**
     * Get module folder
     */
    protected function getModuleFolder(): string
    {
        return ModulePaths::folder();
    }

    /**
     * Get Module namespace.
     */
    protected function getModuleNamespace(): string
    {
        return ModulePaths::namespace();
    }

    /**
     * Get the full module path.
     */
    protected function getModulePath(string $moduleName, ?string $subPath = null): string
    {
        $path = $this->laravel->basePath($this->getModuleFolder().DIRECTORY_SEPARATOR.$moduleName);
        return $subPath ? $path.DIRECTORY_SEPARATOR.$subPath : $path;
    }

    /**
     * Check if the module exists.
     *
     * @param string $moduleName
     *
     * @return bool
     */
    protected function moduleExists(string $moduleName): bool
    {
        return $this->files->exists($this->getModulePath($moduleName));
    }

    /**
     * Get module package name.
     *
     * @param string $moduleName
     *
     * @return string
     */
    protected function getModulePackageName(string $moduleName): string
    {
        $folder = str_replace('\\', '/', $this->getModuleFolder());

        // Composer names are "vendor/package": a nested folder (e.g. "app/Modules")
        // contributes only its last segment as the vendor.
        if (str_contains($folder, '/')) {
            $folder = Str::lower(basename($folder));
        }

        $snakeModuleName = Str::kebab($moduleName);
        return "$folder/$snakeModuleName";
    }

    /**
     * Get the root namespace for the class.
     */
    protected function rootNamespace(): string
    {
        $moduleName = $this->getModuleInput();

        return $this->getModuleNamespace()."\\$moduleName\\";
    }

    /**
     * Check if the module folder exists.
     *
     * @see moduleExists()
     */
    private function moduleAlreadyExists(): bool
    {
        return $this->moduleExists($this->getModuleInput());
    }


    /**
     * Qualify the given model class base name.
     *
     * @param  string  $model
     * @return string
     */
    protected function qualifyModel(string $model): string
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        $modelPath = $this->getModulePath($this->getModuleInput(), 'src'.DIRECTORY_SEPARATOR.'Models');

        if (is_dir($modelPath)) {
            // Module models follow the convention Models\{Name}\{Name} (each model in its own subdirectory).
            // A bare class name like "Licence" must become "Models\Licence\Licence", not just "Models\Licence".
            // A path already containing a backslash (e.g. "Licence\Licence") is used as-is.
            if (! Str::contains($model, '\\')) {
                return $rootNamespace.'Models\\'.$model.'\\'.$model;
            }

            return $rootNamespace.'Models\\'.$model;
        }

        return $rootNamespace.$model;
    }


    /**
     * Get the console command arguments.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::REQUIRED, 'The name of module will be used.'],
            ['name', InputArgument::REQUIRED, 'The name of the class'],
        ];
    }
}
