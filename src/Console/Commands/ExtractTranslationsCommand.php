<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;

#[AsCommand(name: 'module:extract-translation')]
class ExtractTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:extract-translation {module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract all translation files for a specific module.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $translationKeys = $this->findProjectTranslationsKeys();

        $translationFiles = $this->getProjectTranslationFiles();

        foreach ($translationFiles as $file) {
            $translationData = $this->getAlreadyTranslatedKeys($file);
            $added = [];

            $this->alert('Language: '.str_replace('.json', '', basename($file)));

            foreach ($translationKeys as $key => $value) {
                if (! isset($translationData[$key])) {
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

            $this->line('');
        }

        return CommandAlias::SUCCESS;
    }

    private function findProjectTranslationsKeys(): array
    {
        $allKeys = [];
        $viewsDirectories = [
            base_path('modules'.DIRECTORY_SEPARATOR.$this->getModuleInput().DIRECTORY_SEPARATOR.'src'),
            base_path('modules'.DIRECTORY_SEPARATOR.$this->getModuleInput().DIRECTORY_SEPARATOR.'resources'.
                DIRECTORY_SEPARATOR.'views'),
        ];
        $fileExtensions = ['php'];

        foreach ($viewsDirectories as $directory) {
            foreach ($fileExtensions as $extension) {
                $this->getTranslationKeysFromDir($allKeys, $directory, $extension);
            }
        }

        ksort($allKeys);

        return $allKeys;
    }

    private function getTranslationKeysFromDir(array &$keys, string $dirPath, string $fileExt = 'php'): void
    {
        $files = $this->glob($dirPath.DIRECTORY_SEPARATOR."*.$fileExt", GLOB_BRACE);
        $translationMethods = ['__']; // config

        foreach ($files as $file) {
            $content = $this->getFileContent($file);
            foreach ($translationMethods as $translationMethod) {
                $this->getTranslationKeysFromFunction($keys, $translationMethod, $content);
            }
        }
    }

    private function getTranslationKeysFromFunction(array &$keys, string $functionName, string $content): void
    {
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
        $path = base_path('modules'.DIRECTORY_SEPARATOR.$this->getModuleInput().DIRECTORY_SEPARATOR.'lang');
        // config
        return glob($path.DIRECTORY_SEPARATOR.'*.json', GLOB_BRACE);
    }

    private function getAlreadyTranslatedKeys(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException('Language file is not present');
        }

        $content = file_get_contents($filePath);

        if (! $content) {
            throw new InvalidArgumentException('Language file could not be opened.');
        }

        $current = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid translation file');
        }

        if (is_null($current)) {
            throw new InvalidArgumentException('File should be an empty json or a valid JSON format');
        }

        ksort($current);

        return $current;
    }

    private function writeNewTranslationFile(string $filePath, array $translations): void
    {
        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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
        return Str::of((string) $this->argument('module'))->trim()->studly();
    }

    /**
     * @return array<int, string>
     */
    private function glob(string $pattern, int $flags = 0): array
    {
        $files = glob($pattern, $flags);

        if (! $files) {
            $files = [];
        }

        $directories = glob(dirname($pattern).DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR | GLOB_NOSORT);

        if (! $directories) {
            $directories = [];
        }

        return array_reduce($directories, function (array $files, string $dir) use ($pattern, $flags): array {
            return array_merge(
                $files,
                $this->glob($dir.DIRECTORY_SEPARATOR.basename($pattern), $flags)
            );
        }, $files);
    }
}
