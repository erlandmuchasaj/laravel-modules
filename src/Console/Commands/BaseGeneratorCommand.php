<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;

abstract class BaseGeneratorCommand extends GeneratorCommand
{
    /**
     * $this->laravel->basePath(): c:\wamp\www\starter
     * app()->basePath()
     * base_path()
     */

    /**
     * Execute the console command.
     *
     *
     * @throws FileNotFoundException
     */
    public function handle(): ?bool
    {
        // check if module is already created and file exists
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

        $moduleName = $this->getModuleInput();

        // $path = "/modules/{$moduleName}/src/".str_replace('\\', '/', $name).'.php';
        $path = 'modules'.DIRECTORY_SEPARATOR.$moduleName.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.
            str_replace('\\', DIRECTORY_SEPARATOR, $name).'.php';

        return  base_path($path);
    }

    /**
     * Get the first view directory path from the application configuration.
     *
     * @param  string  $path
     */
    protected function viewPath($path = ''): string
    {
        $moduleName = $this->getModuleInput();

        $views = base_path('modules'.DIRECTORY_SEPARATOR.$moduleName.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views');

        return $views.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Resolve the fully-qualified path to the stub.
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
     * We overwrite this command in order to put laravel App root name dynamically.
     *
     * @example $this->rootNamespace() -> $this->laravel->getNamespace()
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
     * based on Module generated
     *
     * @return array<int, string>
     */
    protected function possibleModels(): array
    {
        // $pathSegments = [
        //     'modules',
        //     $this->getModuleInput(),
        //     'src',
        //     'Models'
        // ];
        // // generate a path of modules/ModuleName/src/Models
        // $modelPath = implode(DIRECTORY_SEPARATOR, $pathSegments);

        $modelPath = base_path('modules'.DIRECTORY_SEPARATOR.$this->getModuleInput().DIRECTORY_SEPARATOR.'src'
            .DIRECTORY_SEPARATOR.'Models');

        if (! is_dir($modelPath)) {
            $modelPath = is_dir(app_path('Models')) ? app_path('Models') : app_path();
        }

        return collect((new Finder)->files()->depth(0)->in($modelPath))
            ->map(fn ($file) => $file->getBasename('.php'))
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
        // $pathSegments = [
        //     'modules',
        //     $this->getModuleInput(),
        //     'src',
        //     'Models'
        // ];
        // // generate a path of modules/ModuleName/src/Events
        // $eventPath = implode(DIRECTORY_SEPARATOR, $pathSegments);

        $eventPath = base_path('modules'.DIRECTORY_SEPARATOR.$this->getModuleInput().DIRECTORY_SEPARATOR.'src'
            .DIRECTORY_SEPARATOR.'Events');

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
        $config = $this->laravel['config'];

        return $config->get('modules.folder', 'modules') ?: 'modules';
    }

    /**
     * Get Module namespace.
     */
    protected function getModuleNamespace(): string
    {
        $config = $this->laravel['config'];

        return  $config->get('modules.namespace', 'Modules') ?: 'Modules';
    }

    /**
     * Get the desired class name from the input.
     */
    protected function getModuleInput(): string
    {
        return Str::of((string) $this->argument('module'))->trim()->studly()->toString();
    }

    /**
     * Get the root namespace for the class.
     */
    protected function rootNamespace(): string
    {
        $moduleName = $this->getModuleInput();

        return "Modules\\$moduleName\\";
    }

    /**
     * Check if module folder exists.
     */
    private function moduleAlreadyExists(): bool
    {
        $moduleName = $this->getModuleInput();

        // Next, We will check to see if the Module folder already exists.
        // If it doesn't, we don't want to create other related data.
        // So, we will bail out and  the code is untouched.

        return $this->files->exists('modules'.DIRECTORY_SEPARATOR.$moduleName);
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
