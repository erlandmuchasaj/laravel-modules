<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Exception;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Database\Console\Migrations\TableGuesser;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;

class MigrateMakeCommand extends BaseCommand implements PromptsForMissingInput
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'module:make-migration  {module : The name of the module}
                                            {name : The name of the migration}
                                            {--create= : The table to be created}
                                            {--table= : The table to migrate}
                                            {--path= : The location where the migration file should be created}
                                            {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                                            {--fullpath : Output the full path of the migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration file for module.';

    /**
     * The migration creator instance.
     */
    protected MigrationCreator $creator;

    /**
     * The Composer instance.
     */
    protected Composer $composer;

    /**
     * Create a new migration install command instance.
     *
     * @return void
     */
    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        parent::__construct();

        $this->creator = $creator;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(): void
    {
        // It's possible for the developer to specify the tables to modify in this
        // schema operation. The developer may also specify if this table needs
        // to be freshly created, so we can create the appropriate migrations.
        $name = Str::snake(trim($this->input->getArgument('name')));

        $table = $this->input->getOption('table');

        $create = $this->input->getOption('create') ?: false;

        $moduleName = $this->getModuleInput();

        if (! file_exists(base_path("modules/$moduleName"))) {
            $this->error("Module $moduleName does not exists, You need to create a module first.");

            return;
        }

        // If no table was given as an option but a create option is given then we
        // will use the "create" option as the table name. This allows the devs
        // to pass a table name into this option as a short-cut for creating.
        if (! $table && is_string($create)) {
            $table = $create;

            $create = true;
        }

        // Next, we will attempt to guess the table name if this the migration has
        // "create" in the name. This will allow us to provide a convenient way
        // of creating migrations that create new tables for the application.
        if (! $table) {
            [$table, $create] = TableGuesser::guess($name);
        }

        // if we are still not able to determine table name
        // then the name of the migration file
        // will be the new table name
        if (! $table) {
            $table = $name;
            // $table = Str::plural($name);

            $create = true;
        }

        // Now we are ready to write the migration out to disk. Once we've written
        // the migration out, we will dump-autoload for the entire framework to
        // make sure that the migrations are registered by the class loaders.
        $this->writeMigration($name, $table, $create);

        $this->composer->dumpAutoloads();
    }

    /**
     * Write the migration file to disk.
     *
     *
     * @throws Exception
     */
    protected function writeMigration(string $name, string $table, bool $create): void
    {
        $file = $this->creator->create(
            $name, $this->getMigrationPath(), $table, $create
        );

        if (! $this->option('fullpath')) {
            $file = pathinfo($file, PATHINFO_FILENAME);
        }

        $this->components->info(sprintf('Migration [%s] created successfully.', $file));
    }

    /**
     * Get migration path (either specified by '--path' option or default location).
     */
    protected function getMigrationPath(): string
    {
        if (! is_null($targetPath = $this->input->getOption('path'))) {
            return ! $this->usingRealPath()
                ? $this->laravel->basePath().'/'.$targetPath
                : $targetPath;
        }

        return 'modules'.DIRECTORY_SEPARATOR.$this->getModuleInput().DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
    }

    /**
     * Get the desired class name from the input.
     */
    protected function getModuleInput(): string
    {
        return Str::of((string) $this->input->getArgument('module'))->trim()->studly();
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'What should the migration be named?',
        ];
    }
}
