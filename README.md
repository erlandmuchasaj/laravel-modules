# Laravel modules
A very simple autowired laravel modules generator using laravel best practices and folder structure.
If you like laravel, you will love **laravel modules** and feel just like home.

It follows the same folder structure as a Laravel project, the only difference is instead of **app/**, you have **src/**,
the rest is same.

## Installation

You can install the package via composer:

```shell
composer require erlandmuchasaj/laravel-modules
```

## Usage

Basically this is all you need to do is generate a new module.

```shell
php artisan module:make <ModuleName>
```

When you generate a new module, it will auto register himself automatically by adding the module to the required 
list in `composer.json` file

```shell
    "require": {
        "modules/{{module_name}}": "^1.0"
    },
```

And also it will add automatically repositories 

```shell
    "repositories": [
        {
            "type": "path",
            "url": "modules/*"
        }
    ]
```

and it will run `composer update` in the background to discover the newly generated module,
and you are good to go.

## Creating new module

Here everything is done inside a Module.
Basically one module is a laravel within another Laravel application with all its components, views,
routes, middleware, events, database, seeders, factories and much, much more.

```bash
php artisan module:make <ModuleName>
```

- The module is *auto-discovered* so there is no need to add it to *app/config.php* providers list.
- Then run ``` composer update ``` and you are good to go (_even this is run automatically when you add module `via
  php artisan module:make <ModuleName>`_).


## Artisan commands
```bash
php artisan module:list               Show list of all modules created.
php artisan module:make               Create blueprint for a new module
php artisan module:make-cast          Create a new custom Eloquent cast class
php artisan module:make-channel       Create a new channel class
php artisan module:make-command       Create a new Artisan command
php artisan module:make-component     Create a new component-class for the specified module.
php artisan module:make-controller    Create a new controller class
php artisan module:make-event         Create a new event class
php artisan module:make-exception     Create a new custom exception class
php artisan module:make-factory       Create a new model factory
php artisan module:make-job           Create a new job class
php artisan module:make-listener      Create a new event listener class
php artisan module:make-mail          Create a new email class
php artisan module:make-middleware    Create a new middleware class
php artisan module:make-migration     Create a new migration file for module.
php artisan module:make-model         Create a new Eloquent model class
php artisan module:make-notification  Create a new notification class
php artisan module:make-observer      Create a new observer class
php artisan module:make-policy        Create a new policy class
php artisan module:make-provider      Create a new service provider class
php artisan module:make-request       Create a new form request class
php artisan module:make-resource      Create a new resource
php artisan module:make-rule          Create a new validation rule
php artisan module:make-scope         Create a new scope class
php artisan module:make-seeder        Create a new seeder class
php artisan module:make-test          Create a new test class
php artisan module:make-trait         Make trait
```
---

## Support me

I invest a lot of time and resources into creating [best in class open source packages](https://github.com/erlandmuchasaj?tab=repositories).

If you found this package helpful you can show support by clicking on the following button below and donating some amount to help me work on these projects frequently.

<a href="https://www.buymeacoffee.com/erland" target="_blank">
    <img src="https://www.buymeacoffee.com/assets/img/guidelines/download-assets-2.svg" style="height: 45px; border-radius: 12px" alt="buy me a coffee"/>
</a>

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please see [SECURITY](SECURITY.md) for details.

## Credits

- [Erland Muchasaj](https://github.com/erlandmuchasaj)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
