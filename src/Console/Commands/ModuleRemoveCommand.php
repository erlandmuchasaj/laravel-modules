<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use RuntimeException;
use Throwable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'module:remove', description: 'Remove an existing module')]
class ModuleRemoveCommand extends BaseGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:remove';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'module:remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove an existing module';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Module';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return $this->deleteModule();
    }

    /**
     * Generate the entire structure of a module
     */
    public function deleteModule(): int
    {
        $moduleName = $this->getModuleInput();

        // Next, We will check to see if the Module folder already exists. If it does, we don't want
        // to create the Module and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this Module's files.
        if (!$this->moduleExists($moduleName)) {
            $this->components->error(sprintf('Module [%s] does not exists.', $moduleName));
            return self::FAILURE;
        }

        if (!$this->option('force') &&
            !$this->components->confirm("Remove module [{$moduleName}] permanently? This cannot be undone.")
        ) {
            $this->components->info('Aborted.');
            return self::SUCCESS;
        }


        try {
            $path = $this->getModulePath($moduleName);
            $this->files->deleteDirectory($path);
            $this->components->info("Deleted module files at: $path");
        } catch (Throwable $e) {
            $this->components->error("Failed deleting module folder: " . $e->getMessage());
            return self::FAILURE;
        }

        // ── Step 4: clear stale bootstrap cache so post-autoload-dump artisan boot doesn't hit the missing class ──
        foreach (['services', 'packages'] as $cache) {
            $cacheFile = $this->laravel->bootstrapPath("cache/{$cache}.php");
            if ($this->files->exists($cacheFile)) {
                $this->files->delete($cacheFile);
            }
        }

        $this->components->info("Remove module from composer.json");

        try {
            $this->removeFromComposerJson($moduleName);
        } catch (Throwable $e) {
            $this->components->warn('Could not update composer.json: ' . $e->getMessage());
        }

        $command = sprintf(
            'composer remove "%s" %s %s',
            $this->getModulePackageName($moduleName),
            $this->option('optimize') ? '-o' : '',
            $this->option('quiet') ? '-q' : ''
        );
        passthru($command, $exitCode);

        if ($exitCode !== 0) {
            $this->components->warn('Autoloader rebuild failed. Run `composer dump-autoload` manually.');
            return self::FAILURE;
        }

        $this->components->info(sprintf('Module [%s] removed successfully.', $moduleName));
        return self::SUCCESS;
    }

    /**
     * Remove the module's require entry from the root composer.json.
     * The path repository (./modules/*) is shared across all modules so it is left intact.
     *
     * @throws RuntimeException
     */
    protected function removeFromComposerJson(string $moduleName): void
    {
        $composerPath = base_path('composer.json');

        if (!$this->files->exists($composerPath)) {
            throw new RuntimeException('composer.json not found');
        }

        $content = $this->files->get($composerPath);
        $composer = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid composer.json: ' . json_last_error_msg());
        }

        $packageName = $this->getModulePackageName($moduleName);

        if (!isset($composer['require'][$packageName])) {
            return; // already absent, nothing to do
        }

        unset($composer['require'][$packageName]);

        $json = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException('Failed to encode composer.json');
        }

        $this->files->put($composerPath, $json . PHP_EOL);
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return ''; // Not used in this command
    }

    /**
     * Get the console command arguments.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::REQUIRED, 'Module name that you want to remove.'],
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
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite existing module'],
            ['optimize', 'o', InputOption::VALUE_NONE, 'Optimize autoloader after creation'],
            ['quiet', 'q', InputOption::VALUE_NONE, 'Suppress composer output'],
        ];
    }

}
