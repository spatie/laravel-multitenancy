**DO NOT USE YET, PACKAGE IN DEVELOPMENT**

# Make your Laravel app tenant aware

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-multitenancy.svg?style=flat-square)](https://packagist.org/packages/spatie/:package_name)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spatie/laravel-multitenancy/run-tests?label=tests)](https://github.com/spatie/:package_name/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-multitenancy.svg?style=flat-square)](https://packagist.org/packages/spatie/:package_name)

This package provides a lightweight solution for making a Laravel app tenant aware. It can determine which tenant should be the current tenant for the request. It also allows you to define what should happen when switching the current tenant to another one.

It works for multitenancy projects that need to use one or multiple databases.

Before starting with the package, we highly recommend first watching [this talk by Tom Schlick on multitenancy strategies](https://tomschlick.com/2017/07/25/laracon-2017-multi-tenancy-talk/).

The package contains a lot of niceties such as making queued jobs tenant aware, migrating all tenant databases in one go, an easy way to set a connection on a model, and much more.
 
## Support us

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us). 

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Documentation

You can find the entire documentation for this package [on our documentation site](https://docs.spatie.be/laravel-multitenancy).

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
