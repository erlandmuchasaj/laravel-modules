<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:seed')]
class ModuleSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:seed
                          {module : The module name to seed}
                          {--force : Force run in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed a specific module';

    public function handle(): int
    {
        $module = $this->argument('module');
        $class = "Modules\\{$module}\\Database\\Seeders\\DatabaseSeeder";

        if (!class_exists($class)) {
            $this->error("Seeder class not found: {$class}");
            return self::FAILURE;
        }

        $this->info("Seeding module: {$module}");
        $this->call('db:seed', [
            '--class' => $class,
            '--force' => $this->option('force'),
        ]);

        return self::SUCCESS;
    }
}
