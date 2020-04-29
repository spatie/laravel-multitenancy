# Make your Laravel app usable by multiple tenants

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-multitenancy.svg?style=flat-square)](https://packagist.org/packages/spatie/:package_name)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spatie/laravel-multitenancy/run-tests?label=tests)](https://github.com/spatie/:package_name/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-multitenancy.svg?style=flat-square)](https://packagist.org/packages/spatie/:package_name)


This is where your description should go. Try and limit it to a paragraph or two. Consider adding a small example.

## Support us

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us). 

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-multitenancy
```

## Usage

``` php
$skeleton = new Spatie\Multitenancy();
echo $skeleton->echoPhrase('Hello, Spatie!');
```

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

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
