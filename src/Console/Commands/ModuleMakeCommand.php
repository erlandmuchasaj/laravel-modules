<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Throwable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'module:make', description: 'Create blueprint for a new module')]
class ModuleMakeCommand extends BaseGeneratorCommand
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
     */
    public function handle(): ?bool
    {
        $this->generateModuleStructure();

        return true;
    }

    /**
     * Generate the entire structure of a module
     */
    public function generateModuleStructure(): int
    {
        $moduleName = $this->getModuleInput();

        // First, we need to ensure that the given name is not a reserved word within the PHP
        // language and that the class name will actually be valid. If it is not valid, we
        // can error now and prevent from polluting the filesystem using invalid files.
        if (!$this->validateModuleName($moduleName)) {
            return self::FAILURE;
        }

        // Next, We will check to see if the Module folder already exists. If it does, we don't want
        // to create the Module and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this Module's files.
        if ($this->moduleExists($moduleName)) {
            $this->components->error(sprintf('Module [%s] already exists.', $moduleName));
            return self::FAILURE;
        }

        $this->info("Creating module: $moduleName");

        try {
            // Create directory structure
            $this->createModuleStructure($moduleName);

            // Create all module files
            $this->createModuleFiles($moduleName);

            // Create composer.json for the module
            $this->writeComposerFile($moduleName);

            // Register module in main composer.json
            $this->requireModule($moduleName);

        } catch (Throwable $e) {
            $this->components->error("Failed to create module $moduleName: " . $e->getMessage());

            // Optionally clean up partially created module
            if ($this->option('cleanup-on-error')) {
                $this->cleanupModule($moduleName);
            }
            return self::FAILURE;
        }

        $this->components->info(sprintf('Module [%s] created successfully.', $moduleName));
        return self::SUCCESS;
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

        // if we want to keep the file in repo
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
        return  __DIR__ . '/stubs/module';
    }

    /**
     * Build the class with the given name.
     *
     *
     * @throws FileNotFoundException
     */
    protected function buildProviderClass(string $name, string $stubPath): int|bool
    {
        $folder = $this->getModuleFolder();

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
        $snakeModuleName = Str::kebab($moduleName);

        $content = $this->files->get($this->getStub().'/composer.stub');

        $content = str_replace(['SnakeModuleName', '{{ class }}', '{{class}}'], $snakeModuleName, $content);

        $content = str_replace(['ModuleName', '{{ class }}', '{{class}}'], $moduleName, $content);

        $this->files->put($this->getModulePath($moduleName, 'composer.json'), $content);
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
     * Put the new module in the main JSON file of Laravel
     *
     */
    public function requireModule(string $moduleName): void
    {
        if ($this->option('no-register')) {
            $this->components->info('Module created but not registered. Run composer update manually.');
            return;
        }

        try {
            $this->updateComposerJson($moduleName);

            $packageName = $this->getModulePackageName($moduleName);

            // Instead of full composer update, use composer dump-autoload
            $this->components->info("Installing module: $packageName");

            // $command = sprintf(
            //     'composer require  "%s:*" --no-interaction %s %s',
            //     $packageName,
            //     $this->option('optimize') ? '-o' : '',
            //     $this->option('quiet') ? '-q' : ''
            // );

            $command = sprintf(
                'composer update "%s" --no-interaction %s %s',
                $packageName,
                $this->option('optimize') ? '-o' : '',
                $this->option('quiet') ? '-q' : ''
            );

            passthru($command, $exitCode);

            if ($exitCode === 0) {
                $this->components->info('Module registered successfully!');
            } else {
                throw new RuntimeException('Composer update failed');
            }
        } catch (Throwable $e) {
            $this->components->error('Failed to register module: '.$e->getMessage());
            $this->components->warn('You may need to run "composer update" manually.');
        }
    }

    /**
     * Update composer.json with module information.
     *
     * @throws FileNotFoundException
     */
    protected function updateComposerJson(string $moduleName): void
    {
        $composerPath = base_path('composer.json');

        if (!$this->files->exists($composerPath)) {
            throw new RuntimeException('composer.json not found');
        }

        $content = $this->files->get($composerPath);
        $composer = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid composer.json: '.json_last_error_msg());
        }

        // Add module to require section
        $composer['require'][$this->getModulePackageName($moduleName)] = '*';

        // Add path repository if not exists
        $folder = $this->getModuleFolder();
        $this->addPathRepository($composer, $folder);

        // Write back to composer.json
        $json = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException('Failed to encode composer.json');
        }

        $this->files->put($composerPath, $json.PHP_EOL);
    }

    /**
     * Add path repository to composer.json if it doesn't exist.
     */
    protected function addPathRepository(array &$composer, string $folder): void
    {
        $repositories = $composer['repositories'] ?? [];
        $pathUrl = "./$folder/*";

        // Check if repository already exists
        foreach ($repositories as $repo) {
            if (isset($repo['type'], $repo['url']) &&
                $repo['type'] === 'path' &&
                Str::contains($repo['url'], $pathUrl)
            ) {
                return; // Repository already exists
            }
        }

        // Add new repository
        $composer['repositories'][] = [
            'type' => 'path',
            'url' => $pathUrl,
            'options' => [
                'symlink' => true,
            ],
        ];
    }

    /**
     * Cleanup partially created module on error.
     */
    protected function cleanupModule(string $moduleName): void
    {
        $path = $this->getModulePath($moduleName);

        if ($this->files->exists($path)) {
            $this->files->deleteDirectory($path);
            $this->components->warn("Cleaned up partially created module at: $path");
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

    protected function createModuleStructure(string $moduleName): void
    {
        $baseDirectories = [
            'bootstrap' => true,
            'config' => true,
            'routes' => true,
            'database/factories' => true,
            'database/migrations' => true,
            'database/seeders' => true,
            'resources/views/components' => true,
            'resources/views/errors' => true,
            'resources/views/pages' => true,
            'resources/views/partials' => true,
            'resources/views/layouts/includes' => true,
            'resources/assets/css' => true,
            'resources/assets/js' => true,
            'tests/Feature' => true,
            'tests/Unit' => true,

            'src/Providers' => true,
            'src/Models' => true,
            'src/Console/Commands' => true,
            'src/Http/Controllers' => true,
            'src/Http/Middleware' => true,
            'src/Http/Requests' => true,
            'src/Http/Resources' => true,
            'src/Http/ViewComposers' => true,
        ];

        $optionalDirectories = [
            'src/Enums' => false,
            'src/Broadcasting' => false,
            'src/Exceptions' => false,
            'src/Events' => false,
            'src/Jobs' => false,
            'src/Listeners' => false,
            'src/Mail' => false,
            'src/Notifications' => false,
            'src/Observers' => false,
            'src/Policies' => false,
            'src/Repositories' => false,
            'src/Rules' => false,
            'src/Services' => false,
            'src/Traits' => false,
            'src/Utils' => false,
            'src/Validators' => false,
        ];

        // Create base module directory
        $this->makeDirectory($this->getModulePath($moduleName));

        // Create base directories
        foreach ($baseDirectories as $dir => $gitKeep) {
            $this->makeDirectory($this->getModulePath($moduleName, $dir), $gitKeep);
        }

        // Create optional directories if --all flag is set
        if ($this->option('all')) {
            foreach ($optionalDirectories as $dir => $gitKeep) {
                $this->makeDirectory($this->getModulePath($moduleName, $dir), $gitKeep);
            }
        }
    }

    /**
     * @throws FileNotFoundException
     */
    protected function createModuleFiles(string $moduleName): void
    {
        $namespace = $this->getModuleNamespace();

        // Crate language directories based on Laravel version
        $this->createLanguageStructure($moduleName);

        // Create config file
        $this->createConfigFile($moduleName);

        // Create route files
        $this->createRouteFiles($moduleName);

        // Create helper file
        $this->createHelperFile($moduleName);

        // Create documentation files
        $this->createDocumentationFiles($moduleName);

        // Create service providers
        $this->createServiceProviders($moduleName, $namespace);

        // Create database seeder
        $this->createDatabaseSeeder($moduleName, $namespace);
    }

    protected function createLanguageStructure(string $moduleName): void
    {
        if (version_compare($this->laravel->version(), '9.0.0') >= 0) {
            $this->makeDirectory($this->getModulePath($moduleName, 'lang/en'));
            $this->files->put(
                $this->getModulePath($moduleName, 'lang/en.json'),
                '{}'
            );
            $this->files->put(
                $this->getModulePath($moduleName, 'lang/en/messages.php'),
                $this->getMessagesStub()
            );
        } else {
            $this->makeDirectory($this->getModulePath($moduleName, 'resources/lang/en'));
            $this->files->put(
                $this->getModulePath($moduleName, 'resources/lang/en/messages.php'),
                $this->getMessagesStub()
            );
        }
    }

    protected function createConfigFile(string $moduleName): void
    {
        $this->files->put(
            $this->getModulePath($moduleName, 'config/config.php'),
            $this->getMessagesStub()
        );
    }

    /**
     * @throws FileNotFoundException
     */
    protected function createRouteFiles(string $moduleName): void
    {
        $routes = ['web', 'api', 'channels'];

        foreach ($routes as $route) {
            $stub = $this->files->get($this->getStub()."/routes/$route.stub");
            $this->files->put(
                $this->getModulePath($moduleName, "routes/$route.php"),
                $this->writeFile($stub, $moduleName)
            );
        }
    }

    protected function createHelperFile(string $moduleName): void
    {
        $this->files->put(
            $this->getModulePath($moduleName, 'src/helpers.php'),
            "<?php\n\n/*\n * You can place your custom helper functions.\n */"
        );
    }

    /**
     * @throws FileNotFoundException
     */
    protected function createDocumentationFiles(string $moduleName): void
    {
        $docs = [
            'CHANGELOG.md' => 'CHANGELOG',
            'CODE_OF_CONDUCT.md' => 'CODE_OF_CONDUCT',
            'README.md' => 'README',
            'CONTRIBUTING.md' => 'CONTRIBUTING',
            'LICENSE.md' => 'LICENSE',
            'SECURITY.md' => 'SECURITY',
        ];

        foreach ($docs as $file => $stub) {
            $this->files->put(
                $this->getModulePath($moduleName, $file),
                $this->files->get($this->getStub()."/$stub.stub")
            );
        }
    }

    /**
     * @throws FileNotFoundException
     */
    protected function createServiceProviders(string $moduleName, string $namespace): void
    {
        $providers = [
            'AppServiceProvider',
            // 'BroadcastServiceProvider',
            'EventServiceProvider',
            'RouteServiceProvider',
            'SeedServiceProvider',
        ];

        foreach ($providers as $provider) {
            $this->buildProviderClass(
                "$namespace\\$moduleName\\Providers\\$provider",
                $this->getStub()."/Providers/$provider.stub"
            );
        }
    }

    /**
     * @throws FileNotFoundException
     */
    protected function createDatabaseSeeder(string $moduleName, string $namespace): void
    {
        $seederStub = $this->files->get(__DIR__.'/stubs/seeder.stub');
        $fullClassName = "$namespace\\$moduleName\\Database\\Seeders\\DatabaseSeeder";

        $this->files->put(
            $this->getModulePath($moduleName, 'database/seeders/DatabaseSeeder.php'),
            $this->replaceNamespace($seederStub, $fullClassName)
                ->replaceModuleName($seederStub, $moduleName)
                ->replaceClass($seederStub, 'DatabaseSeeder')
        );
    }

    protected function getMessagesStub(): string
    {
        return "<?php\n\n/*\n * You can place your custom module data in here.\n */\n\nreturn [\n\n];\n";
    }

    /**
     * Validate the given module name.
     *
     * @param  string  $moduleName
     * @return bool
     */
    protected function validateModuleName(string $moduleName): bool
    {
        if ($this->isReservedName($moduleName)) {
            $this->components->error('The name "'.$moduleName.'" is reserved by PHP.');
            return false;
        }

        if (preg_match('([^A-Za-z0-9_/\\\\])', $moduleName)) {
            $this->components->error('Module name contains invalid characters.');
            return false;
        }

        return true;
    }

    /**
     * Get the console command arguments.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::REQUIRED, 'Module name that you want to create.'],
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
            ['no-register', null, InputOption::VALUE_NONE, 'Create module without registering in composer.json'],
            ['optimize', 'o', InputOption::VALUE_NONE, 'Optimize autoloader after creation'],
            ['quiet', 'q', InputOption::VALUE_NONE, 'Suppress composer output'],
            ['cleanup-on-error', null, InputOption::VALUE_NONE, 'Remove partially created module on error'],
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite existing module'],
        ];
    }

}
