<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use ErlandMuchasaj\Modules\Utils\EmCms;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;

#[AsCommand(name: 'app:install')]
class AppInstall extends Command
{
    /**
     * The root namespace to assume when generating URLs to actions.
     */
    private string $base = EmCms::NAME;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrate and seed command, publish assets and config, link storage';

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
        if ($this->emcmsAlreadyInstalled()) {
            $this->line('EMCMS module is already installed for this project.');

            return CommandAlias::FAILURE;
        }

        $this->checkForEnvFile();

        // running `php artisan migrate`
        $this->warn('Step: Migrating all tables into database...');
        $migrate = shell_exec('php artisan migrate:fresh');
        $this->info((string) $migrate);

        // running `php artisan db:seed`
        $this->warn("Step: seeding basic data for $this->base kickstart...");
        $result = shell_exec('php artisan db:seed');
        $this->info((string) $result);

        // running `php artisan vendor:publish --all`
        $this->warn('Step: Publishing Assets and Configurations...');
        $result = shell_exec('php artisan vendor:publish --all');
        $this->info((string) $result);

        // running `php artisan storage:link`
        $this->warn('Step: Linking Storage directory...');
        $result = shell_exec('php artisan storage:link');
        $this->info((string) $result);

        // running `composer dump-autoload`
        $this->warn('Step: Composer Autoload and clear all cache files...');
        $result = shell_exec('composer clear-all');
        $this->info((string) $result);

        $this->info('-----------------------------');
        $this->info('Now, run `php artisan serve` to start using EMCMS system.');
        $this->comment('Create something amazing!');
        $this->info('Cheers!');

        return CommandAlias::SUCCESS;
    }

    /**
     *  Checking .env file and if not found then create .env file.
     *  Then ask for database name, password & username to set
     *  On .env file so that we can easily migrate to our db
     */
    public function checkForEnvFile(): void
    {
        $envExists = File::exists(base_path('.env'));

        if (! $envExists) {
            $this->info('Creating .env file');
            $this->createEnvFile();
        } else {
            $this->info('Great! .env file already exists');
        }
    }

    public function createEnvFile(): void
    {
        try {
            File::copy('.env.example', '.env');
            Artisan::call('key:generate');

            $appName = $this->anticipate('What is your Application name?', [$this->base], $this->base);
            $this->envUpdate('APP_NAME=', $appName);

            $domain = $this->anticipate('What is your domain? ex: example.com', ['localhost'], 'localhost');
            $this->envUpdate('APP_DOMAIN=', $domain);

            $this->addDatabaseDetails();
        } catch (Exception $e) {
            report($e);
            $this->error('Error in creating .env file, please create manually and then run `php artisan migrate` again');
        }
    }

    public function addDatabaseDetails(): void
    {
        $dbName = $this->ask("What is your database name to be used by $this->base");
        $dbUser = $this->anticipate('What is your database username', ['root'], 'root');
        $dbPass = $this->secret('What is your database password');
        $this->envUpdate('DB_DATABASE=', $dbName);
        $this->envUpdate('DB_USERNAME=', $dbUser);
        $this->envUpdate('DB_PASSWORD=', $dbPass);
    }

    /**
     * Update ENV variables
     */
    public static function envUpdate(string $key, string $value): void
    {
        $path = base_path('.env');
        file_put_contents($path, str_replace(
            $key,
            $key.$value,
            (string) file_get_contents($path)
        ));
    }

    /**
     * Determine if EMCMS module system is already installed.
     */
    protected function emcmsAlreadyInstalled(): bool
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        return isset($composer['require']['modules/core']);
    }

    // /**
    //  * @param string $envKey
    //  * @param string|null $envFileContents
    //  * @return bool
    //  */
    // private function isEnvKeySet(string $envKey, ?string $envFileContents = null): bool
    // {
    //     $envFileContents = $envFileContents ?? file_get_contents(app()->environmentFilePath());
    //
    //     return (bool)preg_match("/^{$envKey}=.*?[\s$]/m", (string) $envFileContents);
    // }
    //
    // /**
    //  * @param array $values
    //  * @return bool
    //  */
    // private function setEnvValues(array $values): bool
    // {
    //     $envFilePath = app()->environmentFilePath();
    //
    //     $envFileContents = file_get_contents($envFilePath);
    //
    //     if (!$envFileContents) {
    //         $this->error('Could not read `.env` file!');
    //
    //         return false;
    //     }
    //
    //     $envFileContents = (string) $envFileContents;
    //
    //     if (count($values) > 0) {
    //         foreach ($values as $envKey => $envValue) {
    //             if ($this->isEnvKeySet($envKey, $envFileContents)) {
    //                 $envFileContents = preg_replace("/^{$envKey}=.*?[\s$]/m", "{$envKey}={$envValue}\n", (string) $envFileContents);
    //
    //                 $this->info("Updated {$envKey} with new value in your `.env` file.");
    //             } else {
    //                 $envFileContents .= "{$envKey}={$envValue}\n";
    //
    //                 $this->info("Added {$envKey} to your `.env` file.");
    //             }
    //         }
    //     }
    //
    //     if (!file_put_contents($envFilePath, $envFileContents)) {
    //         $this->error('Updating the `.env` file failed!');
    //
    //         return false;
    //     }
    //
    //     return true;
    // }
}
