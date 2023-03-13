<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'module:make-model')]
class ModelMakeCommand extends BaseGeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-model';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'module:make-model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    /**
     * Execute the console command.
     *
     *
     * @throws FileNotFoundException
     */
    public function handle(): ?bool
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }

        if ($this->option('all')) {
            $this->input->setOption('traits', true);
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            // $this->input->setOption('resource', true);
            // $this->input->setOption('api', true);
            $this->input->setOption('policy', true);
        }

        if ($this->option('traits')) {
            $this->createModelTraits();
        }

        if ($this->option('factory')) {
            $this->createFactory();
        }

        if ($this->option('migration')) {
            $this->createMigration();
        }

        if ($this->option('seed')) {
            $this->createSeeder();
        }

        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController();
        }

        if ($this->option('policy')) {
            $this->createPolicy();
        }

        return true;
    }

    /**
     * Create a model factory for the model.
     */
    protected function createFactory(): void
    {
        $factory = Str::studly(class_basename($this->argument('name')));

        $this->call('module:make-factory', [
            'module' => $this->getModuleInput(),
            'name' => "{$factory}Factory",
            '--model' => $this->qualifyClass($this->getNameInput()),
        ]);
    }

    /**
     * Create a migration file for the model.
     */
    protected function createMigration(): void
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

        if ($this->option('pivot')) {
            $table = Str::singular($table);
        }

        $this->call('module:make-migration', [
            'module' => $this->getModuleInput(),
            'name' => "create_{$table}_table",
            '--create' => $table,
        ]);
    }

    /**
     * Create a seeder file for the model.
     */
    protected function createSeeder(): void
    {
        $seeder = Str::studly(class_basename($this->argument('name')));

        $this->call('module:make-seed', [
            'module' => $this->getModuleInput(),
            'name' => "{$seeder}Seeder",
        ]);
    }

    /**
     * Create a controller for the model.
     */
    protected function createController(): void
    {
        $controller = Str::studly(class_basename($this->argument('name')));

        $modelName = $this->qualifyClass($this->getNameInput());

        $moduleName = $this->getModuleInput();

        $this->call('module:make-controller', array_filter([
            'module' => $moduleName,
            'name' => "{$controller}Controller",
            '--model' => $this->option('resource') || $this->option('api') ? $modelName : null,
            '--api' => $this->option('api'),
            '--requests' => $this->option('requests') || $this->option('all'),
        ]));
    }

    /**
     * Create a policy file for the model.
     */
    protected function createPolicy(): void
    {
        $policy = Str::studly(class_basename($this->argument('name')));

        $moduleName = $this->getModuleInput();

        $this->call('module:make-policy', [
            'module' => $moduleName,
            'name' => "{$policy}Policy",
            '--model' => $this->qualifyClass($this->getNameInput()),
        ]);
    }

    /**
     * createModelTraits
     */
    protected function createModelTraits(): void
    {
        $modelName = $this->getNameInput();

        $moduleName = $this->getModuleInput();

        $modelTraits = ['AccessorTrait', 'MethodTrait', 'MutatorTrait', 'RelationsTrait', 'ScopesTrait'];

        foreach ($modelTraits as $traitName) {
            $this->call('module:make-trait', array_filter([
                'module' => $moduleName,
                'name' => $traitName,
                '--model' => $modelName,
            ]));
        }
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        if ($this->option('traits')) {
            return $this->resolveStubPath('/stubs/model-with-traits.stub');
        }

        if ($this->option('pivot')) {
            return $this->resolveStubPath('/stubs/model.pivot.stub');
        }

        if ($this->option('morph-pivot')) {
            return $this->resolveStubPath('/stubs/model.morph-pivot.stub');
        }

        return $this->resolveStubPath('/stubs/model.stub');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Models\\'.$this->getNameInput();
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'Generate a migration, seeder, factory, and resource controller for the model'],
            ['controller', 'c', InputOption::VALUE_NONE, 'Create a new controller for the model'],
            ['factory', 'f', InputOption::VALUE_NONE, 'Create a new factory for the model'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
            ['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model'],
            ['morph-pivot', null, InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom polymorphic intermediate table model'],
            ['policy', null, InputOption::VALUE_NONE, 'Create a new policy for the model'],
            ['seed', 's', InputOption::VALUE_NONE, 'Create a new seeder file for the model'],
            ['pivot', 'p', InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom intermediate table model'],
            ['resource', 'r', InputOption::VALUE_NONE, 'Indicates if the generated controller should be a resource controller'],
            ['api', null, InputOption::VALUE_NONE, 'Indicates if the generated controller should be an API controller'],
            ['traits', 't', InputOption::VALUE_NONE, 'Separate eloquent attributes method in traits'],
            ['requests', 'R', InputOption::VALUE_NONE, 'Create new form request classes and use them in the resource controller'],
        ];
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        collect($this->components->choice('Would you like any of the following?', [
            'none',
            'all',
            'factory',
            'form requests',
            'migration',
            'policy',
            'resource controller',
            'seed',
        ], default: 0, multiple: true))
            ->reject('none')
            ->map(fn ($option) => match ($option) {
                'resource controller' => 'resource',
                'form requests' => 'requests',
                default => $option,
            })
            ->each(fn ($option) => $input->setOption($option, true));
    }
}
