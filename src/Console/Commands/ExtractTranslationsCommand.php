<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'module:extract-translation')]
class ExtractTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:extract-translation {module} {--create : Create missing language files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract all translation files for a specific module.';

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        if (!$this->moduleExists()) {
            $this->components->error(
                sprintf('Module [%s] does not exist.', $this->getModuleInput())
            );
            return self::FAILURE;
        }

        $this->info("Extracting translations for module: {$this->getModuleInput()}");

        $translationKeys = $this->findProjectTranslationsKeys();

        if (empty($translationKeys)) {
            $this->warn('No translation keys found in the module.');
            return self::SUCCESS;
        }

        $this->info("Found ".count($translationKeys)." translation keys.");

        $translationFiles = $this->getProjectTranslationFiles();

        if (empty($translationFiles)) {
            return self::SUCCESS;
        }

        foreach ($translationFiles as $file) {
            $translationData = $this->getAlreadyTranslatedKeys($file);

            $added = [];

            $this->newLine();
            $this->alert('Language: '.str_replace('.json', '', basename($file)));

            foreach ($translationKeys as $key => $value) {
                if (!isset($translationData[$key])) {
                    $translationData[$key] = $value;
                    $added[] = $key;
                    $this->info(" - Added: $key");
                }
            }

            if (! empty($added)) {
                $this->line('Updating translation file...');
                $this->writeNewTranslationFile($file, $translationData);
                $this->info('Translation file have been updated!');
            } else {
                $this->warn('Nothing new found for this language.');
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function findProjectTranslationsKeys(): array
    {
        $allKeys = [];
        $viewsDirectories = [
            $this->getModuleSourcePath(),
            $this->getModuleViewsPath(),
        ];

        foreach ($viewsDirectories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $this->getTranslationKeysFromDir($allKeys, $directory);
        }

        ksort($allKeys);

        return $allKeys;
    }

    /**
     * @param array<string, string> $keys
     * @param string $dirPath
     * @return void
     */
    private function getTranslationKeysFromDir(array &$keys, string $dirPath): void
    {

        $files = $this->glob($dirPath.DIRECTORY_SEPARATOR."*.php", GLOB_BRACE);
        $translationMethods = $this->getTranslationMethods(); // config

        foreach ($files as $file) {
            $content = $this->getFileContent($file);

            foreach ($translationMethods as $translationMethod) {
                $this->getTranslationKeysFromFunction($keys, $translationMethod, $content);
            }
        }
    }

    /**
     * @param array<string, string> $keys
     * @param string $functionName
     * @param string $content
     * @return void
     */
    private function getTranslationKeysFromFunction(array &$keys, string $functionName, string $content): void
    {
        // Match __('key') or __("key") with proper escaping support
        preg_match_all("#$functionName *\( *((['\"])((?:\\\\\\2|.)*?)\\2)#", $content, $matches);

        $matches = $matches[1] ?? [];

        foreach ($matches as $match) {
            $quote = $match[0];
            $match = trim($match, $quote);
            $key = ($quote === '"') ? stripcslashes($match) : str_replace(["\\'", '\\\\'], ["'", '\\'], $match);

            if (! empty($match)) {
                $keys[$key] = $match;
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function getProjectTranslationFiles(): array
    {
        $path = $this->getModuleLangPath();
        if (!is_dir($path)) {
            if ($this->option('create')) {
                File::makeDirectory($path, 0755, true);
                $this->info("Created language directory: $path");
            } else {
                $this->warn("Language directory not found: $path");
                return [];
            }
        }

        $files = glob($path.DIRECTORY_SEPARATOR.'*.json', GLOB_BRACE);

        if (empty($files) && $this->option('create')) {
            // Create a default en.json file
            $defaultFile = $path.DIRECTORY_SEPARATOR.'en.json';
            File::put($defaultFile, '{}');
            $this->info("Created default translation file: en.json");
            $files = [$defaultFile];
        }

        return $files ?: [];
    }

    /**
     * @param string $filePath
     *
     * @return array<string, string>
     * @throws FileNotFoundException
     */
    private function getAlreadyTranslatedKeys(string $filePath): array
    {
        if (!File::exists($filePath)) {
            throw new InvalidArgumentException('Language file is not present');
        }

        $content = File::get($filePath);
        $current = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid translation file: '.json_last_error_msg());
        }

        if (!is_array($current)) {
            throw new InvalidArgumentException('File should be a valid JSON object');
        }

        ksort($current);

        return $current;
    }

    /**
     * @param string $filePath
     * @param array<string, string> $translations
     * @return void
     */
    private function writeNewTranslationFile(string $filePath, array $translations): void
    {
        File::put(
            $filePath,
            json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function getFileContent(string $filePath): string
    {
        $content = file_get_contents($filePath);

        if ($content) {
            return str_replace("\n", ' ', $content);
        }

        return '';
    }

    /**
     * Get the desired module name from the input.
     */
    protected function getModuleInput(): string
    {
        return Str::of((string) $this->argument('module'))->trim()->studly()->toString();
    }

    /**
     * Check if the module exists.
     */
    protected function moduleExists(): bool
    {
        $moduleName = $this->getModuleInput();
        return is_dir(base_path('modules'.DIRECTORY_SEPARATOR.$moduleName));
    }

    /**
     * Get the module base path.
     */
    protected function getModulePath(string $subPath = ''): string
    {
        $moduleName = $this->getModuleInput();
        $path = base_path('modules'.DIRECTORY_SEPARATOR.$moduleName);

        return $subPath ? $path.DIRECTORY_SEPARATOR.$subPath : $path;
    }

    /**
     * Get the module language path.
     */
    protected function getModuleLangPath(): string
    {
        return $this->getModulePath('lang');
    }

    /**
     * Get the module source path.
     */
    protected function getModuleSourcePath(): string
    {
        return $this->getModulePath('src');
    }

    /**
     * Get the module views path.
     */
    protected function getModuleViewsPath(): string
    {
        return $this->getModulePath('resources'.DIRECTORY_SEPARATOR.'views');
    }

    /**
     * Get all possible functions used for translation methods.
     * @return string[]
     */
    private function getTranslationMethods(): array
    {
        return [
            '__',
            'trans',
            'trans_choice',
            '@lang', // Blade directive
        ];
    }

    /**
     * @return array<int, string>
     */
    private function glob(string $pattern, int $flags = 0): array
    {
        $files = glob($pattern, $flags) ?: [];
        $directories = glob(dirname($pattern).DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR | GLOB_NOSORT) ?: [];

        return array_reduce($directories, function (array $files, string $dir) use ($pattern, $flags): array {
            return array_merge(
                $files,
                $this->glob($dir.DIRECTORY_SEPARATOR.basename($pattern), $flags)
            );
        }, $files);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::REQUIRED, 'The name of the command'],
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
            ['create', null, InputOption::VALUE_NONE, 'Create missing language files'],
        ];
    }
}
