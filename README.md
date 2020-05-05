**DO NOT USE YET, PACKAGE IN DEVELOPMENT**

# Make your Laravel app tenant aware

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-multitenancy.svg?style=flat-square)](https://packagist.org/packages/spatie/:package_name)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spatie/laravel-multitenancy/run-tests?label=tests)](https://github.com/spatie/:package_name/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-multitenancy.svg?style=flat-square)](https://packagist.org/packages/spatie/:package_name)

This package provides a lightweight solution for making a Laravel app tenant aware. It assumes that every tenant has its own database, and that there is also a "landlord" database with global data.

A large number of projects don't need separate databases for tenants, and could use a single database to store all data for all tenants. We highly recommend first watching [this talk by Tom Schlick on multitenancy strategies](https://tomschlick.com/2017/07/25/laracon-2017-multi-tenancy-talk/). If you, after careful consideration, want to go for the multi database route, this might be the package for you.

Tenant configuration is stored in the `tenants` table in the landlord database.

By default, two databases connections are set up. The `tenant` connection, and the `landlord` connection.

By default, the `tenant` connection is not set. To make a tenant current you can call `makeCurrent` on it.

```php
Tenant::current(); // returns null

$tenant = Tenant::whereDomain($host)->first();
$tenant->makeCurrent(); // the `tenant` connection now uses the `database` of this tenant

Tenant::current(); // returns the `$tenant` instance
```

The package contains a lot of niceties such as making queued jobs tenant aware, migrating all tenant databases in one go, an easy way to set a connection on a model, and much more.
 
## Support us

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us). 

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

TODO: write full installation docs

You can install the package via composer:

```bash
composer require spatie/laravel-multitenancy
```

`tenant`, `landlord` connections

```bash
php artisan migrate --path=database/migrations/landlord --database=landlord 
```

Migrations: `php artisan tenants:migrate `

## Usage

Coming soon...

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

The code of this package is based on the code shown in [the Multitenancy in Laravel series](https://www.youtube.com/watch?v=592EgykFOz4)  by Mohamed Said

- [Mohammed Said](https://github.com/themsaid)
- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## Alternatives

This package aims to be a lightweight solution to make your app multitenancy aware. If you need more features, take a look at [hyn/multi-tenant](https://tenancy.dev/).

Another option you could look at it is [stancl/tenancy](https://github.com/stancl/tenancy).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
