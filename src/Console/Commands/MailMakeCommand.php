<?php

namespace ErlandMuchasaj\Modules\Console\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'module:make-mail')]
class MailMakeCommand extends BaseGeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-mail';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'module:make-mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new email class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Mail';

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

        if ($this->option('markdown')) {
            $this->writeMarkdownTemplate();
        }

        return true;
    }

    /**
     * Write the Markdown template for the mailable.
     */
    protected function writeMarkdownTemplate(): void
    {
        $path = $this->viewPath(
            str_replace('.', '/', $this->getView()).'.blade.php'
        );

        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        if ($file = file_get_contents(__DIR__.'/stubs/markdown.stub')) {
            $this->files->put($path, $file);
        }
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     *
     * @throws FileNotFoundException
     */
    protected function buildClass($name): string
    {
        $class = parent::buildClass($name);

        if ($this->option('markdown') !== false) {
            $class = str_replace(['DummyView', '{{ view }}'], $this->getView(), $class);
        }

        return $class;
    }

    /**
     * Get the view name.
     */
    protected function getView(): string
    {
        $view = $this->option('markdown');

        if (! $view) {
            $name = str_replace('\\', '/', $this->argument('name'));

            $view = 'mail.'.collect(explode('/', $name))
                    ->map(fn ($part) => Str::kebab($part))
                    ->implode('.');
        }

        return $view;
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath(
            $this->option('markdown') !== false
                ? '/stubs/markdown-mail.stub'
                : '/stubs/mail.stub');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Mail';
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the mailable already exists'],
            ['markdown', 'm', InputOption::VALUE_OPTIONAL, 'Create a new Markdown template for the mailable'],
        ];
    }
}
