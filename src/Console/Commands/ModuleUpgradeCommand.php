<?php

declare(strict_types=1);

namespace ErlandMuchasaj\Modules\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Migrates existing modules from the old composer path-repo mechanism
 * (extra.laravel.providers + path repository in composer.json) to the new
 * bootstrap/modules.php registry introduced in this package version.
 *
 * Safe to run multiple times — all operations are idempotent.
 */
#[AsCommand(name: 'module:upgrade', description: 'Migrate modules from composer path-repo registration to the bootstrap/modules.php registry')]
class ModuleUpgradeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:upgrade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate modules from composer path-repo registration to the bootstrap/modules.php registry';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $folder = $this->getModuleFolder();

        $this->components->info("Scanning for modules in: {$folder}/");

        $modules = $this->discoverModules($folder);


        if (empty($modules)) {
            $this->components->warn("No modules found in {$folder}/. Nothing to upgrade.");
            return self::SUCCESS;
        }

        $this->line(sprintf(
            '  Found <info>%d</info> module(s): <comment>%s</comment>',
            count($modules),
            implode(', ', $modules)
        ));
        $this->newLine();

        // ── Load current state ────────────────────────────────────────────────
        $registryPath = $this->laravel->bootstrapPath('modules.php');
        $registeredProviders = $this->files->exists($registryPath)
            ? (require $registryPath)
            : [];

        $composerPath = base_path('composer.json');
        if (!$this->files->exists($composerPath)) {
            $this->components->error('composer.json not found.');
            return self::FAILURE;
        }

        $composer = json_decode($this->files->get($composerPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->components->error('Invalid composer.json: '.json_last_error_msg());
            return self::FAILURE;
        }


        // ── Process each module ───────────────────────────────────────────────
        $namespace        = $this->getModuleNamespace();
        $addedToRegistry  = [];
        $addedToAutoload  = [];

        foreach ($modules as $moduleName) {
            $provider = "{$namespace}\\{$moduleName}\\Providers\\AppServiceProvider";

            // Registry
            if (!in_array($provider, $registeredProviders, true)) {
                $registeredProviders[] = $provider;
                $addedToRegistry[]     = $moduleName;
                $this->line("  <info>[+]</info> {$moduleName} → bootstrap/modules.php");
            } else {
                $this->line("  <comment>[=]</comment> {$moduleName} → already in bootstrap/modules.php");
            }

            // PSR-4 autoload entries
            $mainKey = "{$namespace}\\{$moduleName}\\";
            if (!isset($composer['autoload']['psr-4'][$mainKey])) {
                $composer['autoload']['psr-4'][$mainKey]                                          = "{$folder}/{$moduleName}/src/";
                $composer['autoload']['psr-4']["{$namespace}\\{$moduleName}\\Database\\Factories\\"] = "{$folder}/{$moduleName}/database/factories/";
                $composer['autoload']['psr-4']["{$namespace}\\{$moduleName}\\Database\\Seeders\\"]   = "{$folder}/{$moduleName}/database/seeders/";
                $addedToAutoload[] = $moduleName;
                $this->line("  <info>[+]</info> {$moduleName} → composer.json autoload.psr-4");
            } else {
                $this->line("  <comment>[=]</comment> {$moduleName} → autoload entries already present");
            }
        }

        $this->newLine();

        // ── Optionally strip the old path-repo mechanism ──────────────────────
        if ($this->option('clean')) {
            $stripped = $this->stripOldRegistration($composer, $modules, $folder);
            if ($stripped) {
                $this->components->info('Removed old path-repo and require entries from composer.json.');
            } else {
                $this->components->info('No old path-repo entries found in composer.json.');
            }
        } else {
            $this->components->warn(
                'Old path-repo entries in composer.json were NOT removed. '.
                'Re-run with --clean when you are ready to strip them.'
            );
        }

        // ── Write files ───────────────────────────────────────────────────────
        if (empty($addedToRegistry) && empty($addedToAutoload) && !$this->option('clean')) {
            $this->components->info('Everything already up to date. No files changed.');
            return self::SUCCESS;
        }

        try {
            $this->writeRegistryFile($registryPath, $registeredProviders);

            $json = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new RuntimeException('Failed to encode composer.json');
            }

            $this->files->put($composerPath, $json.PHP_EOL);
        } catch (RuntimeException $e) {
            $this->components->error('Failed to write files: '.$e->getMessage());
            return self::FAILURE;
        }

        // ── Rebuild autoloader ────────────────────────────────────────────────
        $this->newLine();
        $this->components->info('Rebuilding autoloader…');

        $command = sprintf(
            'composer dump-autoload --no-interaction%s%s',
            $this->option('optimize') ? ' -o' : '',
            $this->option('quiet') ? ' -q' : ''
        );

        passthru($command, $exitCode);

        if ($exitCode !== 0) {
            $this->components->warn('Autoloader rebuild failed. Run `composer dump-autoload` manually.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Upgrade complete. Modules are now registered via bootstrap/modules.php.');

        return self::SUCCESS;
    }

    /**
     * Scan the modules directory for valid modules.
     *
     * A directory is a valid module if it contains src/Providers/AppServiceProvider.php —
     * the same heuristic used by ModuleCacheManager::discoverModules().
     *
     * @return array<int, string>
     */
    protected function discoverModules(string $folder): array
    {
        $basePath = base_path($folder);

        if (!is_dir($basePath)) {
            return [];
        }

        return collect($this->files->directories($basePath))
            ->map(fn(string $dir) => basename($dir))
            ->filter(function (string $name) use ($basePath) {
                return $this->files->exists(
                    $basePath.DIRECTORY_SEPARATOR.$name
                    .DIRECTORY_SEPARATOR.'src'
                    .DIRECTORY_SEPARATOR.'Providers'
                    .DIRECTORY_SEPARATOR.'AppServiceProvider.php'
                );
            })
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Remove old path-repo require and repositories entries written by the
     * previous version of module:make.
     *
     * Only removes entries that match the discovered module set and the
     * configured folder wildcard — it will not touch unrelated repositories.
     *
     * Returns true if anything was changed.
     */
    protected function stripOldRegistration(array &$composer, array $modules, string $folder): bool
    {
        $changed = false;

        // Remove per-module require entries (e.g. "modules/my-module": "*")
        foreach ($modules as $moduleName) {
            $packageName = $folder.'/'.Str::kebab($moduleName);

            if (isset($composer['require'][$packageName])) {
                unset($composer['require'][$packageName]);
                $changed = true;
            }

            if (isset($composer['require-dev'][$packageName])) {
                unset($composer['require-dev'][$packageName]);
                $changed = true;
            }
        }

        // Remove path repositories pointing at ./{folder}/* (the wildcard glob)
        if (isset($composer['repositories'])) {
            $wildcard = "./{$folder}/*";

            $before = count($composer['repositories']);
            $composer['repositories'] = array_values(array_filter(
                $composer['repositories'],
                static fn(array $repo) => !(
                    ($repo['type'] ?? '') === 'path' &&
                    str_contains($repo['url'] ?? '', $wildcard)
                ),
            ));

            if (count($composer['repositories']) !== $before) {
                $changed = true;
            }

            if (empty($composer['repositories'])) {
                unset($composer['repositories']);
            }
        }

        return $changed;
    }

    /**
     * Serialize a provider list to the registry file.
     *
     * Duplicates the format produced by ModuleMakeCommand::writeModuleRegistry()
     * so both files are byte-identical when the same modules are registered.
     */
    protected function writeRegistryFile(string $path, array $providers): void
    {
        $entries = array_map(static fn(string $p) => "  {$p}::class,", $providers);

        $this->files->put($path, implode(PHP_EOL, [
            '<?php',
            '',
            '// Auto-generated by module:make — do not edit manually.',
            'return [',
            implode(PHP_EOL, $entries),
            '];',
            '',
        ]));
    }

    /**
     * Read the modules.folder config value.
     */
    protected function getModuleFolder(): string
    {
        return $this->laravel['config']->get('modules.folder', 'modules') ?: 'modules';
    }

    /**
     * Read the modules.namespace config value.
     */
    protected function getModuleNamespace(): string
    {
        return $this->laravel['config']->get('modules.namespace', 'Modules') ?: 'Modules';
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function getOptions(): array
    {
        return [
            ['clean', null, InputOption::VALUE_NONE, 'Also remove old path-repo require and repositories entries from composer.json'],
            ['optimize', 'o', InputOption::VALUE_NONE, 'Optimize autoloader after upgrade'],
            ['quiet', 'q', InputOption::VALUE_NONE, 'Suppress composer output'],
        ];
    }
}
