<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use ErlandMuchasaj\Modules\Support\SeedOrchestrator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:seed-clear')]
class ModuleSeedClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:seed-clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the seed orchestrator state';

    public function handle(SeedOrchestrator $orchestrator): int
    {
        $orchestrator->reset();

        $this->info('Seed orchestrator cleared successfully!');

        return self::SUCCESS;
    }
}
