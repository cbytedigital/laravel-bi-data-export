# Laravel BI Data Export
[![PHP from Packagist](https://img.shields.io/packagist/php-v/cbytedigital/laravel-bi-data-export.svg)](https://packagist.org/packages/cbytedigital/laravel-bi-data-export)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/cbytedigital/laravel-bi-data-export.svg)](https://packagist.org/packages/cbytedigital/laravel-bi-data-export)
[![Software License](https://img.shields.io/packagist/l/cbytedigital/laravel-bi-data-export.svg)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/cbytedigital/laravel-bi-data-export.svg)](https://packagist.org/packages/cbytedigital/laravel-bi-data-export)

A [Laravel](https://laravel.com) package which can be used for easily and periodically exporting large datasets for BI purposes. Includes built in functionality for excluding or redacting columns and exporting to CSV on a Laravel filesystem disk.

The package uses database cursors and streaming to support working with large datasets and keep memory usage to a minimum for poor little webservers.

## Installation

Use composer to install this package:

```bash
$ composer require cbytedigital/laravel-bi-data-export
```

Optional: The service provider will automatically get registered. Or you may manually add the service provider in your config/app.php file:
```php
'providers' => [
    // ...
    CbyteDigital\BiDataExport\BiDataExportServiceProvider::class,
];
```

You should publish the config with:
```php
php artisan vendor:publish --provider="CbyteDigital\BiDataExport\BiDataExportServiceProvider"
```

## Usage

Currently, only exporting to .CSV is supported. Which is the most usual method of exporting large datasets.

To include your models for the export, configure your models to use the ```BiExportable``` trait. If you require to export for example a pivot table, which does usually not have a dedicated model, you can manually add the table and required columns/config for exporting to the configuration file.

Add the command for exporting on a schedule:
```php
$schedule->command(ExportBiDataCommand::class)->dailyAt('23:00');
```

Or call it manually:
```php
php artisan bi:export-data
```

## Testing
Run the tests with:
```bash
$ composer test
```

## Support

| Version | Laravel Version | PHP Version |
|---- |-----------------|-------------|
| 1.x | \>= 8.x         | \>=8.0      |

## Postcardware

This package is completely free to use. If it makes it to your production environment we would highly appreciate you sending us a postcard from your hometown! 👏🏼

Our address is: CBYTE Software B.V., Heuvelkamp 2a, 6658DE Beneden-Leeuwen, Netherlands.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
