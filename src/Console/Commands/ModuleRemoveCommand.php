<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Throwable;
use RuntimeException;
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
    public function handle(): ?bool
    {
        $this->deleteModule();

        return true;
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

        $path = $this->getModulePath($moduleName);

        try {
            // Delete module files
            $this->info("Deleting module: $moduleName");
            $this->files->deleteDirectory($path);
            $this->components->info("Deleted module files at: $path");
        } catch (Throwable $e) {
            $this->components->error("Failed deleting module folder: " . $e->getMessage());
            return self::FAILURE;
        }

        try {
            $this->components->info("Remove module from composer.json");
            $command = sprintf(
                'composer remove "%s" %s %s',
                $this->getModulePackageName($moduleName),
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
            $this->components->error("Failed to update composer.json: " . $e->getMessage());
            $this->components->warn('You may need to edit composer.json manually.');
            return self::FAILURE;
        }

        $this->components->info("Module [$moduleName] removed successfully.");
        return self::SUCCESS;
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
