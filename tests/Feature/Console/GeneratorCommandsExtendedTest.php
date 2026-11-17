<?php

use Illuminate\Support\Facades\File;

const EXT_MODULE = 'ScaffoldExt';

beforeEach(function () {
    $this->modulesPath = base_path('modules');
    $this->moduleName  = EXT_MODULE;
    $this->modulePath  = $this->modulesPath . '/' . $this->moduleName;

    if (! is_dir($this->modulePath)) {
        $this->artisan('module:make', ['module' => $this->moduleName, '--no-register' => true]);
    }
});

afterEach(function () {
    if (File::isDirectory($this->modulePath)) {
        File::deleteDirectory($this->modulePath);
    }
});

// ──────────────────────────────────────────────
// module:make-cast
// ──────────────────────────────────────────────

it('module:make-cast produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-cast', ['module' => $this->moduleName, 'name' => 'ArticleCast'])
        ->assertExitCode(0);

    $file = $this->modulePath . '/src/Casts/ArticleCast.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Casts");
});

// ──────────────────────────────────────────────
// module:make-channel
// ──────────────────────────────────────────────

it('module:make-channel produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-channel', ['module' => $this->moduleName, 'name' => 'ArticleChannel'])
        ->assertExitCode(0);

    $file = $this->modulePath . '/src/Broadcasting/ArticleChannel.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Broadcasting");
});

// ──────────────────────────────────────────────
// module:make-component
// ──────────────────────────────────────────────

it('module:make-component creates both a PHP class and a Blade view', function () {
    $this->artisan('module:make-component', ['module' => $this->moduleName, 'name' => 'ArticleCard'])
        ->assertExitCode(0);

    $phpFile   = $this->modulePath . '/src/View/Components/ArticleCard.php';
    $bladeFile = $this->modulePath . '/resources/views/components/article-card.blade.php';

    expect(file_exists($phpFile))->toBeTrue()
        ->and(file_get_contents($phpFile))->toContain("namespace Modules\\{$this->moduleName}\\View\\Components")
        ->and(file_exists($bladeFile))->toBeTrue();
});

it('module:make-component --view creates only the Blade view', function () {
    $this->artisan('module:make-component', ['module' => $this->moduleName, 'name' => 'ArticleCard', '--view' => true])
        ->assertExitCode(0);

    $phpFile   = $this->modulePath . '/src/View/Components/ArticleCard.php';
    $bladeFile = $this->modulePath . '/resources/views/components/article-card.blade.php';

    expect(file_exists($phpFile))->toBeFalse()
        ->and(file_exists($bladeFile))->toBeTrue();
});

// ──────────────────────────────────────────────
// module:make-command
// ──────────────────────────────────────────────

it('module:make-command produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-command', ['module' => $this->moduleName, 'name' => 'ProcessArticles'])
        ->assertExitCode(0);

    $file = $this->modulePath . '/src/Console/Commands/ProcessArticles.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Console\\Commands");
});

// ──────────────────────────────────────────────
// module:make-exception
// ──────────────────────────────────────────────

it('module:make-exception produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-exception', ['module' => $this->moduleName, 'name' => 'ArticleNotFoundException'])
        ->assertExitCode(0);

    $file = $this->modulePath . '/src/Exceptions/ArticleNotFoundException.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Exceptions");
});

// ──────────────────────────────────────────────
// module:make-factory
// ──────────────────────────────────────────────

it('module:make-factory appends Factory suffix and mirrors the guessed model subdirectory', function () {
    $this->artisan('module:make-factory', ['module' => $this->moduleName, 'name' => 'Article'])
        ->assertExitCode(0);

    // "Article" resolves to Models\Article\Article, so path and namespace both carry "Article".
    $file = $this->modulePath . '/database/factories/Article/ArticleFactory.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Database\\Factories\\Article;")
        ->and(file_get_contents($file))->toContain('class ArticleFactory');
});

it('module:make-factory writes the file where its namespace says when the model has a subdirectory', function () {
    $this->artisan('module:make-factory', [
        'module' => $this->moduleName,
        'name' => 'Post',
        '--model' => "Modules\\{$this->moduleName}\\Models\\Post\\Post",
    ])->assertExitCode(0);

    $file = $this->modulePath . '/database/factories/Post/PostFactory.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_exists($this->modulePath . '/database/factories/PostFactory.php'))->toBeFalse()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Database\\Factories\\Post;")
        ->and(file_get_contents($file))->toContain('class PostFactory');
});

it('module:make-model --factory links the model to its factory with newFactory()', function () {
    $this->artisan('module:make-model', ['module' => $this->moduleName, 'name' => 'Comment', '--factory' => true])
        ->assertExitCode(0);

    $model = file_get_contents($this->modulePath . '/src/Models/Comment/Comment.php');
    $factoryClass = "Modules\\{$this->moduleName}\\Database\\Factories\\Comment\\CommentFactory";

    expect($model)->toContain("use {$factoryClass};")
        ->and($model)->toContain('protected static function newFactory(): CommentFactory')
        ->and($model)->toContain('return CommentFactory::new();')
        ->and(file_exists($this->modulePath . '/database/factories/Comment/CommentFactory.php'))->toBeTrue();

    // The generated model must stay valid PHP.
    exec('php -l ' . escapeshellarg($this->modulePath . '/src/Models/Comment/Comment.php'), $output, $code);
    expect($code)->toBe(0);
});

it('module:make-model without --factory adds no newFactory()', function () {
    $this->artisan('module:make-model', ['module' => $this->moduleName, 'name' => 'Tag'])
        ->assertExitCode(0);

    expect(file_get_contents($this->modulePath . '/src/Models/Tag/Tag.php'))->not->toContain('newFactory');
});

// ──────────────────────────────────────────────
// module:make-mail
// ──────────────────────────────────────────────

it('module:make-mail produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-mail', ['module' => $this->moduleName, 'name' => 'ArticlePublished'])
        ->assertExitCode(0);

    $file = $this->modulePath . '/src/Mail/ArticlePublished.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Mail");
});

// ──────────────────────────────────────────────
// module:make-middleware
// ──────────────────────────────────────────────

it('module:make-middleware produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-middleware', ['module' => $this->moduleName, 'name' => 'EnsureArticleIsPublished'])
        ->assertExitCode(0);

    $file = $this->modulePath . '/src/Http/Middleware/EnsureArticleIsPublished.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Http\\Middleware");
});

// ──────────────────────────────────────────────
// module:make-migration
// ──────────────────────────────────────────────

it('module:make-migration creates a timestamped migration file in database/migrations', function () {
    $this->artisan('module:make-migration', ['module' => $this->moduleName, 'name' => 'create_articles_table'])
        ->assertExitCode(0);

    $migDir   = $this->modulePath . '/database/migrations';
    $migFiles = glob($migDir . '/*_create_articles_table.php');

    expect($migFiles)->not->toBeEmpty()
        ->and(file_exists($migFiles[0]))->toBeTrue();
});

// ──────────────────────────────────────────────
// module:make-provider
// ──────────────────────────────────────────────

it('module:make-provider produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-provider', ['module' => $this->moduleName, 'name' => 'ArticleServiceProvider'])
        ->assertExitCode(0);

    $file = $this->modulePath . '/src/Providers/ArticleServiceProvider.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Providers");
});

// ──────────────────────────────────────────────
// module:make-request
// ──────────────────────────────────────────────

it('module:make-request produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-request', ['module' => $this->moduleName, 'name' => 'StoreArticleRequest'])
        ->assertExitCode(0);

    $file = $this->modulePath . '/src/Http/Requests/StoreArticleRequest.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Http\\Requests");
});

// ──────────────────────────────────────────────
// module:make-resource
// ──────────────────────────────────────────────

it('module:make-resource produces a file at the correct path with the correct namespace', function () {
    $this->artisan('module:make-resource', ['module' => $this->moduleName, 'name' => 'ArticleResource'])
        ->assertExitCode(0);

    $file = $this->modulePath . '/src/Http/Resources/ArticleResource.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Http\\Resources");
});

it('module:make-resource --collection uses the collection stub', function () {
    $this->artisan('module:make-resource', ['module' => $this->moduleName, 'name' => 'ArticleCollection', '--collection' => true])
        ->assertExitCode(0);

    $file = $this->modulePath . '/src/Http/Resources/ArticleCollection.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Http\\Resources");
});

// ──────────────────────────────────────────────
// module:make-seeder
// ──────────────────────────────────────────────

it('module:make-seeder places the file under database/seeders with the correct namespace', function () {
    $this->artisan('module:make-seeder', ['module' => $this->moduleName, 'name' => 'ArticleSeeder'])
        ->assertExitCode(0);

    $file = $this->modulePath . '/database/seeders/ArticleSeeder.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Database\\Seeders");
});

// ──────────────────────────────────────────────
// module:make-test
// ──────────────────────────────────────────────

it('module:make-test creates a Feature test by default', function () {
    $this->artisan('module:make-test', ['module' => $this->moduleName, 'name' => 'ArticleFeatureTest'])
        ->assertExitCode(0);

    $file = $this->modulePath . '/tests/Feature/ArticleFeatureTest.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Tests\\Feature");
});

it('module:make-test --unit creates a Unit test', function () {
    $this->artisan('module:make-test', ['module' => $this->moduleName, 'name' => 'ArticleUnitTest', '--unit' => true])
        ->assertExitCode(0);

    $file = $this->modulePath . '/tests/Unit/ArticleUnitTest.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Tests\\Unit");
});

// ──────────────────────────────────────────────
// module:make-trait
// ──────────────────────────────────────────────

it('module:make-trait produces a file under src/Traits with the correct namespace', function () {
    $this->artisan('module:make-trait', ['module' => $this->moduleName, 'name' => 'HasArticles'])
        ->assertExitCode(0);

    $file = $this->modulePath . '/src/Traits/HasArticles.php';

    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toContain("namespace Modules\\{$this->moduleName}\\Traits");
});

// ──────────────────────────────────────────────
// module:make-view
// ──────────────────────────────────────────────

it('module:make-view creates a Blade view file in resources/views', function () {
    $this->artisan('module:make-view', ['module' => $this->moduleName, 'name' => 'articles.index'])
        ->assertExitCode(0);

    $file = $this->modulePath . '/resources/views/articles/index.blade.php';

    expect(file_exists($file))->toBeTrue();
});
