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

Currently, only exporting to .CSV is supported. Which is the most usual method of exporting large datasets. Lucky, you can write your own implemention of a export job and reference the class in the config.

The export can be used directly in requests (sync), but it is recommended to use background workers and queueing.

To include your models for the export, configure your models to use the ```BiExportable``` trait.
```php
class Client extends Model
{
    use BiExportable;
}
```

If desired define the selected and/or hidden fields in your model as follows (optional):
```php
class Client extends Model
{
    use BiExportable;
    
    // Default behaviour
    public $biExportable = '*';

    // Or select specific columns
    public $biExportable = [
        'id',
        'username'
    ];

    // Values of hidden fields will be replaced
    public $biHidden = [
        'first_name',
        'last_name'
    ];

    // Define the placeholder value for hidden fields.
    // If not defined will resort to using the variable defined in the config.
    public $biHiddenText = 'REDACTED';
}
```

If you require to export for example a pivot table, which does usually not have a dedicated model, you can manually add the table and required columns/config for exporting to the configuration file.
```php
<?php

return [
    /**
     * Define models for exporting
     * ...
     */
    'models' => [
        \App\Models\Model::class
    ],

    /**
     * Define tables for exporting
     * ...
     */
    'tables' => [
        'partners' => [
            'columns' => '*'
        ]
    ],

    /**
     * Determines the export action. You can define your own implementation here.
     */
    'export_job' => CbyteDigital\BiDataExport\Jobs\ExportBiToCsv::class,

    /**
     * Determines the export location.
     */
    'export_disk' => env('BI_EXPORT_DISK', 's3'),

    /**
     * CSV export job delimiter value
     */
    'export_csv_delimiter' => env('BI_EXPORT_CSV_DELIMITER', ';'),

    /**
     * Default replacement value if not overwritten by the model or tables config.
     */
    'default_hidden_text' => env('BI_HIDDEN_TEXT', 'REDACTED'),

    /**
     * Ability to add a prefix to the filename. For example: {prefix}table.sql
     */
    'filename_prefix' => env('BI_FILENAME_PREFIX'),

    /**
     * Ability to add a suffix to the filename. For example: table{suffix}.sql
     */
    'filename_suffix' => env('BI_FILENAME_SUFFIX')
];
```

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
