<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;

#[AsCommand(name: 'module:list')]
class ListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show list of all modules.';

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
        $this->components->twoColumnDetail('<fg=gray>Index/Name</>');
        collect($this->getModules())->each(function ($item, $key) {
            $this->components->twoColumnDetail("[{$key}] / ".basename($item));
        });

        return CommandAlias::SUCCESS;
    }

    /**
     * Return a list of all modules
     *
     * @return array<int, string>
     */
    public function getModules(): array
    {
        return File::directories(base_path('modules'));
    }
}
