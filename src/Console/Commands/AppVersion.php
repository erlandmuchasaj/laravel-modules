<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;

#[AsCommand(name: 'app:version')]
class AppVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Displays current version of App installed';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->comment('App version: Laravel v'.app()->version());

        return CommandAlias::SUCCESS;
    }
}
