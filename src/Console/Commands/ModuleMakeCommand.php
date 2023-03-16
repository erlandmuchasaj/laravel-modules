<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'module:make')]
class ModuleMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'module:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create blueprint for a new module';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Module';

    /**
     * Execute the console command.
     *
     *
     * @throws FileNotFoundException
     */
    public function handle(): ?bool
    {
        $this->generateModuleStructure();

        return true;
    }

    /**
     * Generate the entire structure of a module
     *
     *
     * @throws FileNotFoundException
     */
    public function generateModuleStructure(): void
    {
        $moduleName = $this->getModuleInput();

        // First we need to ensure that the given name is not a reserved word within the PHP
        // language and that the class name will actually be valid. If it is not valid we
        // can error now and prevent from polluting the filesystem using invalid files.
        if ($this->isReservedName($moduleName)) {
            $this->error('The name "'.$moduleName.'" is reserved by PHP.');

            return;
        }

        // Next we check that the name does not contain any non-supported values.
        if (preg_match('([^A-Za-z0-9_/\\\\])', $moduleName)) {
            throw new InvalidArgumentException('Module name contains invalid characters.');
        }

        $config = $this->laravel['config'];

        $folder = $config->get('modules.folder', 'modules') ?: 'modules';
        $namespace = $config->get('modules.namespace', 'Modules') ?: 'Modules';

        // Next, We will check to see if the Module folder already exists. If it does, we don't want
        // to create the Module and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this Module' files.
        if ($this->files->exists($folder.DIRECTORY_SEPARATOR.$moduleName)) {
            $this->components->error(
                sprintf('Module [%s] already exists.', $moduleName)
            );

            return;
        }

        /**
         * Create Module Folder Structures
         */
        $this->makeDirectory("$folder/$moduleName");
        $this->makeDirectory("$folder/$moduleName/bootstrap", true);
        $this->makeDirectory("$folder/$moduleName/config", true);
        $this->makeDirectory("$folder/$moduleName/database/factories", true);
        $this->makeDirectory("$folder/$moduleName/database/migrations", true);
        $this->makeDirectory("$folder/$moduleName/database/seeders", true);

        // Add a default seeder on all modules
        // this will be used as an entry
        // point for seeding.
        $seederStub = $this->files->get(__DIR__.'/stubs/seeder.stub');
        $this->files->put(
            "$folder/$moduleName/database/seeders/DatabaseSeeder.php",
            $this->replaceNamespace($seederStub, $namespace.'\\'.$moduleName.'\\Database\Seeders\DatabaseSeeder')
                ->replaceModuleName($seederStub, $moduleName)
                ->replaceClass($seederStub, 'DatabaseSeeder')
        );

        // there is a change in structure for translations from v8 to v9.
        if (version_compare(app()->version(), '9.0.0') >= 0) {
            $this->makeDirectory("$folder/$moduleName/lang/en");
            $this->files->put("$folder/$moduleName/lang/en.json", '{}');
            $this->files->put("$folder/$moduleName/lang/en/messages.php", "<?php \n\n/*\n * You can place your custom module messages in here.\n */\n \nreturn [\n\n];\n");
        } else {
            $this->makeDirectory("$folder/$moduleName/resources/lang/en");
            $this->files->put("$folder/$moduleName/resources/lang/en/messages.php", "<?php \n\n/*\n * You can place your custom module messages in here.\n */\n \nreturn [\n\n];\n");
        }

        $this->makeDirectory("$folder/$moduleName/resources/views/components", true);
        $this->makeDirectory("$folder/$moduleName/resources/views/errors", true);
        $this->makeDirectory("$folder/$moduleName/resources/views/pages", true);
        $this->makeDirectory("$folder/$moduleName/resources/views/partials", true);
        $this->makeDirectory("$folder/$moduleName/resources/views/layouts/includes", true);
        $this->makeDirectory("$folder/$moduleName/resources/assets/css", true);
        $this->makeDirectory("$folder/$moduleName/resources/assets/js", true);
        $this->makeDirectory("$folder/$moduleName/tests/Feature", true);
        $this->makeDirectory("$folder/$moduleName/tests/Unit", true);
        $this->makeDirectory("$folder/$moduleName/routes");

        $this->makeDirectory("$folder/$moduleName/src");
        $this->makeDirectory("$folder/$moduleName/src/Http");
        $this->makeDirectory("$folder/$moduleName/src/Console");
        $this->makeDirectory("$folder/$moduleName/src/Providers");
        $this->makeDirectory("$folder/$moduleName/src/Models", true);
        $this->makeDirectory("$folder/$moduleName/src/Console/Commands", true);
        $this->makeDirectory("$folder/$moduleName/src/Http/Controllers", true);
        $this->makeDirectory("$folder/$moduleName/src/Http/Middleware", true);
        $this->makeDirectory("$folder/$moduleName/src/Http/Requests", true);

        if ($this->option('all')) {
            $this->makeDirectory("$folder/$moduleName/src/Enums");
            $this->makeDirectory("$folder/$moduleName/src/Broadcasting");
            $this->makeDirectory("$folder/$moduleName/src/Exceptions");
            $this->makeDirectory("$folder/$moduleName/src/Events");
            $this->makeDirectory("$folder/$moduleName/src/Http/Controllers/Api");
            $this->makeDirectory("$folder/$moduleName/src/Http/Middleware/Api");
            $this->makeDirectory("$folder/$moduleName/src/Http/Resources");
            $this->makeDirectory("$folder/$moduleName/src/Http/ViewComposers");
            $this->makeDirectory("$folder/$moduleName/src/Jobs");
            $this->makeDirectory("$folder/$moduleName/src/Listeners");
            $this->makeDirectory("$folder/$moduleName/src/Mail");
            $this->makeDirectory("$folder/$moduleName/src/Notifications");
            $this->makeDirectory("$folder/$moduleName/src/Observers");
            $this->makeDirectory("$folder/$moduleName/src/Policies");
            $this->makeDirectory("$folder/$moduleName/src/Repositories");
            $this->makeDirectory("$folder/$moduleName/src/Rules");
            $this->makeDirectory("$folder/$moduleName/src/Services");
            $this->makeDirectory("$folder/$moduleName/src/Traits");
            $this->makeDirectory("$folder/$moduleName/src/Utils");
            $this->makeDirectory("$folder/$moduleName/src/Validators");
        }

        /**$folder
         * Add .gitkeep files in folders in order to keep them in repositories
         *
         * @note if we do not add .gitkeep the folder won't be pushed on repository.
         */
        $this->files->put("$folder/$moduleName/config/config.php", "<?php \n\n/*\n * You can place your custom module configuration in here.\n */\n \nreturn [\n\n];\n");

        $stubWebRoute = $this->files->get($this->getStub().'/routes/web.stub');
        $stubApiRoute = $this->files->get($this->getStub().'/routes/api.stub');
        $stubBroadcastChannel = $this->files->get($this->getStub().'/routes/channels.stub');

        $this->files->put("$folder/$moduleName/routes/web.php", $this->writeFile($stubWebRoute, $moduleName));
        $this->files->put("$folder/$moduleName/routes/api.php", $this->writeFile($stubApiRoute, $moduleName));
        $this->files->put("$folder/$moduleName/routes/channels.php", $this->writeFile($stubBroadcastChannel, $moduleName));
        $this->files->put("$folder/$moduleName/src/helpers.php", "<?php \n\n/*\n * You can place your custom helper functions.\n */");

        $this->files->put("$folder/$moduleName/CHANGELOG.md", $this->files->get($this->getStub().'/CHANGELOG.stub'));
        $this->files->put("$folder/$moduleName/CODE_OF_CONDUCT.md", $this->files->get($this->getStub().'/CODE_OF_CONDUCT.stub'));
        $this->files->put("$folder/$moduleName/README.md", $this->files->get($this->getStub().'/README.stub'));
        $this->files->put("$folder/$moduleName/CONTRIBUTING.md", $this->files->get($this->getStub().'/CONTRIBUTING.stub'));
        $this->files->put("$folder/$moduleName/LICENSE.md", $this->files->get($this->getStub().'/LICENSE.stub'));
        $this->files->put("$folder/$moduleName/SECURITY.md", $this->files->get($this->getStub().'/SECURITY.stub'));

        $this->buildProviderClass("$namespace\\$moduleName\\Providers\\AppServiceProvider", $this->getStub().'/Providers/AppServiceProvider.stub');
        // $this->buildProviderClass("$namespace\\{$moduleName}\\Providers\\BroadcastServiceProvider", $this->getStub().'/Providers/BroadcastServiceProvider.stub');
        $this->buildProviderClass("$namespace\\$moduleName\\Providers\\EventServiceProvider", $this->getStub().'/Providers/EventServiceProvider.stub');
        $this->buildProviderClass("$namespace\\$moduleName\\Providers\\RouteServiceProvider", $this->getStub().'/Providers/RouteServiceProvider.stub');
        $this->buildProviderClass("$namespace\\$moduleName\\Providers\\SeedServiceProvider", $this->getStub().'/Providers/SeedServiceProvider.stub');

        $this->writeComposerFile($moduleName);

        $this->requireModule($moduleName);

        $this->components->info(
            sprintf('Module [%s] created successfully.', $moduleName)
        );

        passthru('composer update');
        // passthru('composer update');
        // passthru('composer dump -o -n -q');
    }

    /**
     * Get the desired class name from the input.
     */
    protected function getModuleInput(): string
    {
        return Str::of(strval($this->argument('module')))->trim()->studly();
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     */
    protected function makeDirectory($path, bool $gitKeep = false): string
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0777, true, true);
        }

        // if we want to keep teh  file in repo
        if ($gitKeep) {
            $this->files->put("$path/.gitkeep", '');
        }

        return $path;
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return __DIR__.'/stubs/module';
    }

    /**
     * Build the class with the given name.
     *
     *
     * @throws FileNotFoundException
     */
    protected function buildProviderClass(string $name, string $stubPath): int|bool
    {
        $config = $this->laravel['config'];

        $folder = $config->get('modules.folder', 'modules') ?: 'modules';

        $stub = $this->files->get($stubPath);

        $moduleName = $this->getModuleInput();

        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        $filePath = $folder.DIRECTORY_SEPARATOR.$moduleName.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Providers'.DIRECTORY_SEPARATOR.$class.'.php';

        return $this->files->put(
            $filePath,
            $this->replaceNamespace($stub, $name)->replaceModuleName($stub, $moduleName)->replaceClass($stub, $name)
        );
    }

    /**
     * @throws FileNotFoundException
     */
    public function writeComposerFile(string $moduleName): void
    {
        $config = $this->laravel['config'];

        $folder = $config->get('modules.folder', 'modules') ?: 'modules';

        $content = $this->files->get($this->getStub().'/composer.stub');

        $snakeModuleName = Str::kebab($moduleName);

        $content = str_replace(['SnakeModuleName', '{{ class }}', '{{class}}'], $snakeModuleName, $content);

        $content = str_replace(['ModuleName', '{{ class }}', '{{class}}'], $moduleName, $content);

        $this->files->put("$folder/$moduleName/composer.json", $content);
    }

    public function writeFile(string $stub, string $moduleName): string
    {
        $snakeModuleName = Str::kebab($moduleName);

        $stub = str_replace(
            ['DummyModuleName', '{{ moduleName }}', '{{moduleName}}'],
            $moduleName,
            $stub
        );

        return str_replace(
            ['DummySnakeModuleName', '{{ snakeModuleName }}', '{{snakeModuleName}}'],
            $snakeModuleName,
            $stub
        );
    }

    /**
     * Put the new module on the main json file of Laravel
     *
     *
     * @throws FileNotFoundException
     */
    public function requireModule(string $moduleName): void
    {
        $config = $this->laravel['config'];

        $folder = $config->get('modules.folder', 'modules') ?: 'modules';

        $snakeModuleName = Str::kebab($moduleName);

        $content = $this->files->get('composer.json');

        $phpArray = json_decode($content, true);

        $phpArray['require'][$folder.'/'.$snakeModuleName] = '^1.0';

        // auto wire the path repository for modules loading.
        // this is done only once.
        $exists = collect(Arr::wrap($phpArray['repositories'] ?? []))->contains(function ($value) use ($folder) {
            return $value['type'] === 'path' && Str::contains($value['url'], "./$folder/*");
        });

        if (! $exists) {
            $phpArray['repositories'][] = [
                'type' => 'path',
                'url' => "./$folder/*",
                // 'option' => [
                //     'symlink' => true,
                // ]
            ];
        }

        if ($file = json_encode($phpArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) {
            $this->files->put('composer.json', $file);
        }
    }

    /**
     * Replace the Module class name for the given stub.
     *
     * @return $this
     */
    protected function replaceModuleName(string &$stub, string $moduleName): static
    {
        $stub = str_replace(
            ['DummyModuleName', '{{ moduleName }}', '{{moduleName}}'],
            $moduleName,
            $stub
        );

        return $this;
    }

    /**
     * Get the console command arguments.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::REQUIRED, 'Module name'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function getOptions(): array
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'Generate a module with all folder structure.'],
        ];
    }
}
