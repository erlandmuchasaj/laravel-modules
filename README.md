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
            "url": "./modules/*"
        }
    ]
```

and it will run `composer update` in the background to discover the newly generated module,
and you are good to go.

## Creating new module

Here everything is done inside a Module.
Basically one module is a Laravel within another Laravel application with all its components, views,
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
If you want to register _Events_, _Policies_, _Observers_, _Middleware_, _Commands_, _Providers_ etc. you can do so on 
`./modules/ModuleName/Providers/EventServiceProvider` and `./modules/ModuleName/Providers/AppServiceProvider`

### Examples:

Registering new provider. Go to `./modules/ModuleName/Providers/AppServiceProvider` and add the provider to 
`$providers` array as follows:
```php
    /**
     * Get the services provided by the provider.
     *
     * @var array<int, class-string>
     */
    protected array $providers = [
        ...
        NewServiceProvider::class, // <= new provider added here
    ];
```

Subscribing and/or listening to events. Go to `./modules/ModuleName/Providers/EventServiceProvider` and on the 
`$subscribe` array add the EventSubscriber listener:
```php
    /**
     * Class event subscribers.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [
        AnnouncementEventListener::class
    ];
```

Or you can just listen to events as you normally would in Laravel:
```php
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];
```

Adding new policy. Go to `./modules/ModuleName/Providers/AppServiceProvider` and add to `$policies` array the `Model` 
and corresponding `Policy` as follows:
```php
    /**
     * The policy mappings for the application.
     * Announcement::class is the model <==
     * 
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Announcement::class => AnnouncementPolicy::class,
    ];

```

Adding new observer. Go to `./modules/ModuleName/Providers/AppServiceProvider` and add to `$observers` array the 
`Model` and the corresponding `Observer` as follows:
```php
    /**
     * The policy mappings for the application.
     * Announcement::class is the model <==
     * 
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Announcement::class => AnnouncementObserver::class,
    ];

```

Adding new command. Go to `./modules/ModuleName/Providers/AppServiceProvider` and add to `$commands` array the name 
of the command as follows:
```php
    /**
     * The available command shortname.
     *
     * @var array<int, class-string>
     */
    protected array $commands = [
        AppVersion::class,
        SendAnnouncementNotifications::class
    ];
```

Middleware. There are 3 types of middleware that you can add. Global, Grouped and route. 
```php
    /**
     * The application's global middleware stack.
     *
     * @var array<int, class-string>
     */
    protected array $middleware = [
        ...
        AddXHeader::class, // <== Add global middleware here
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string>>
     */
    protected array $middlewareGroups = [
        'web' => [
            RememberLocale::class,
        ],
        'api' => [
            IdempotencyMiddleware::class,
        ],
        // <== Add grouped middleware here. you can add to existing group or create new groups.
    ];

    /**
     * The application's route middleware.
     * These middleware may be assigned to group or used individually.
     *
     * @var array<string, class-string>
     */
    protected array $routeMiddleware = [
        ...
        'ip_whitelist' => IpWhitelist::class, // <== Add route middleware here
    ];

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
